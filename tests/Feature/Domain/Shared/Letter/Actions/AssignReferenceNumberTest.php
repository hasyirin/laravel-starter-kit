<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Letter\Actions\AssignReferenceNumber;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Validation\ValidationException;
use Tests\Fixtures\Support\NoticeLetter;
use Tests\Fixtures\Support\NumberedNoticeLetter;
use Tests\Fixtures\Support\YearlyNoticeLetter;

beforeEach(function (): void {
    config(['system.letters.types' => [
        'numbered' => NumberedNoticeLetter::class,
        'notice' => NoticeLetter::class,
        'yearly' => YearlyNoticeLetter::class,
    ]]);
    $this->organization = Organization::factory()->create();
    $this->printer = User::factory()->create();
    Profile::factory()->for($this->organization)->for($this->printer)->create();
});

function userIn(Organization $organization): User
{
    $user = User::factory()->create();
    Profile::factory()->for($organization)->for($user)->create();

    return $user;
}

function assignNumber(Letter $letter, ?User $printer = null): Letter
{
    return AssignReferenceNumber::make()->execute($letter, $printer ?? test()->printer);
}

test('assigning twice gives one number', function (): void {
    $letter = Letter::factory()->ofType('numbered')->create();

    assignNumber($letter);
    assignNumber($letter);

    expect($letter->fresh()?->reference_number)->toBe('N/1')
        ->and(assignNumber(Letter::factory()->ofType('numbered')->create())->reference_number)->toBe('N/2');
});

test('two letters of one sequence get 1 and 2', function (): void {
    $first = assignNumber(Letter::factory()->ofType('numbered')->create());
    $second = assignNumber(Letter::factory()->ofType('numbered')->create());

    expect([$first->sequence_number, $second->sequence_number])->toBe([1, 2])
        ->and([$first->reference_number, $second->reference_number])->toBe(['N/1', 'N/2'])
        ->and($first->sequence_key)->toBe("numbered:{$this->organization->id}")
        ->and($first->sequence_cycle)->toBe(1);
});

test('two organizations get separate sequences', function (): void {
    $other = userIn(Organization::factory()->create());

    $first = assignNumber(Letter::factory()->ofType('numbered')->create());
    $second = assignNumber(Letter::factory()->ofType('numbered')->create(), $other);

    expect([$first->reference_number, $second->reference_number])->toBe(['N/1', 'N/1'])
        ->and($first->sequence_key)->not->toBe($second->sequence_key);
});

test('the key uses the signature profile, not the newest profile', function (): void {
    $signingOrganization = Organization::factory()->create();
    $signer = userIn($signingOrganization);
    $letter = Letter::factory()->ofType('numbered')->create();
    $letter->act(Act::Sign, $signer);
    Profile::factory()->for(Organization::factory())->for($signer)->create();

    expect(assignNumber($letter)->sequence_key)->toBe("numbered:{$signingOrganization->id}");
});

test('without a signer the key uses the printer organization', function (): void {
    expect(assignNumber(Letter::factory()->ofType('numbered')->create())->sequence_key)->toBe("numbered:{$this->organization->id}");
});

test('a printer without an organization and no signer is refused', function (): void {
    $printer = User::factory()->create();

    expect(fn (): Letter => assignNumber(Letter::factory()->ofType('numbered')->create(), $printer))->toThrow(LogicException::class);
});

test('the key does not change when the signer changes after the number', function (): void {
    $letter = Letter::factory()->ofType('numbered')->create();
    assignNumber($letter);
    $key = $letter->fresh()?->sequence_key;

    $letter->act(Act::Sign, userIn(Organization::factory()->create()));
    assignNumber($letter);

    expect($letter->fresh()?->sequence_key)->toBe($key);
});

test('an empty date becomes today', function (): void {
    $this->travelTo('2026-05-06 10:00:00');

    expect(assignNumber(Letter::factory()->ofType('numbered')->create())->date?->toDateString())->toBe('2026-05-06');
});

test('a kept date stays', function (): void {
    $letter = Letter::factory()->ofType('numbered')->create(['date' => '2026-01-02']);

    expect(assignNumber($letter)->date?->toDateString())->toBe('2026-01-02');
});

test('manual numbering without a reference number is refused', function (): void {
    expect(fn (): Letter => assignNumber(Letter::factory()->ofType('notice')->create()))->toThrow(ValidationException::class);
});

test('manual numbering keeps a typed reference number', function (): void {
    $letter = Letter::factory()->ofType('notice')->create(['reference_number' => 'REF/9']);

    expect(assignNumber($letter)->reference_number)->toBe('REF/9');
});

test('a yearly sequence starts again at 1 in the next year', function (): void {
    $this->travelTo('2026-12-31 10:00:00');
    $last = assignNumber(Letter::factory()->ofType('yearly')->create());

    $this->travelTo('2027-01-02 10:00:00');
    $next = assignNumber(Letter::factory()->ofType('yearly')->create());

    expect($last->reference_number)->toBe('N/1/2026')
        ->and($next->reference_number)->toBe('N/1/2027')
        ->and($next->sequence_cycle)->toBe(2);
});

test('a stale second instance gets the first instance number', function (): void {
    $letter = Letter::factory()->ofType('numbered')->create();
    $stale = Letter::query()->findOrFail($letter->id);

    assignNumber($letter);
    $second = assignNumber($stale);

    expect($second->reference_number)->toBe('N/1')
        ->and($stale->reference_number)->toBe('N/1')
        ->and(assignNumber(Letter::factory()->ofType('numbered')->create())->reference_number)->toBe('N/2');
});
