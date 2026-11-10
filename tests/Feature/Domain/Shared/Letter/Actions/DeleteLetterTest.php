<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Actions\AssignReferenceNumber;
use App\Domain\Shared\Letter\Actions\DeleteLetter;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Hasyirin\RunningNumber\Models\RunningNumberUndo;
use Tests\Fixtures\Support\NoticeLetter;
use Tests\Fixtures\Support\NumberedNoticeLetter;
use Tests\Fixtures\Support\YearlyNoticeLetter;

test('deleting a letter soft deletes it', function (): void {
    config(['system.letters.types' => ['notice' => NoticeLetter::class]]);
    $letter = Letter::factory()->create();

    DeleteLetter::make()->execute($letter);

    expect(Letter::query()->find($letter->id))->toBeNull()
        ->and(Letter::withTrashed()->find($letter->id)?->trashed())->toBeTrue()
        ->and(Organization::query()->count())->toBe(1);
});

function numberedLetter(string $type = 'numbered', ?User $printer = null): Letter
{
    config(['system.letters.types' => ['numbered' => NumberedNoticeLetter::class, 'yearly' => YearlyNoticeLetter::class]]);

    if (! $printer instanceof User) {
        $printer = User::factory()->create();
        Profile::factory()->for(Organization::factory())->for($printer)->create();
    }

    return AssignReferenceNumber::make()->execute(Letter::factory()->ofType($type)->create(), $printer);
}

test('a delete in the current cycle records an undo', function (): void {
    $letter = numberedLetter();

    DeleteLetter::make()->execute($letter);

    expect(RunningNumberUndo::query()->sole()->number)->toBe(1);
});

test('a deleted number is not given again', function (): void {
    $printer = User::factory()->create();
    Profile::factory()->for(Organization::factory())->for($printer)->create();
    $letter = numberedLetter(printer: $printer);
    DeleteLetter::make()->execute($letter);

    $next = numberedLetter(printer: $printer);

    expect($next->sequence_key)->toBe($letter->sequence_key)
        ->and($next->reference_number)->toBe('N/2');
});

test('a delete after the cycle ended records nothing and keeps the number', function (): void {
    $this->travelTo('2026-06-01 10:00:00');
    $letter = numberedLetter('yearly');

    $this->travelTo('2027-06-01 10:00:00');
    DeleteLetter::make()->execute($letter);

    $deleted = Letter::withTrashed()->findOrFail($letter->id);

    expect(RunningNumberUndo::query()->count())->toBe(0)
        ->and($deleted->trashed())->toBeTrue()
        ->and($deleted->reference_number)->toBe('N/1/2026')
        ->and($deleted->sequence_number)->toBe(1);
});

test('a delete of a typed reference number records nothing', function (): void {
    config(['system.letters.types' => ['notice' => NoticeLetter::class]]);
    $letter = Letter::factory()->create(['reference_number' => 'REF/1', 'sequence_key' => 'notice:1']);

    DeleteLetter::make()->execute($letter);

    expect(RunningNumberUndo::query()->count())->toBe(0);
});
