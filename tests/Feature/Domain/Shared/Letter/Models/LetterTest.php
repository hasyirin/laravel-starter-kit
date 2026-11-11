<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\CarbonCopy;
use App\Domain\User\Models\Profile;
use Hasyirin\Actor\Facades\Actor;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Models\Permit;
use Tests\Fixtures\Support\NoticeLetter;

beforeEach(function (): void {
    Schema::create('permits', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->boolean('hidden')->default(false);
        $table->boolean('locked')->default(false);
        $table->timestampsTz();
        $table->softDeletesTz();
    });

    Relation::morphMap(['permit' => Permit::class]);
    config(['system.letters.types' => ['notice' => NoticeLetter::class]]);

    $this->permit = Permit::query()->create(['name' => 'Permit']);
});

test('carbon copies round-trip through the database', function (): void {
    $letter = $this->permit->letters()->create([
        'type' => 'notice',
        'carbon_copies' => [new CarbonCopy('Finance', 'department', 4), new CarbonCopy('Neighbour')],
    ]);

    $copies = $letter->fresh()->carbon_copies;

    expect($copies)->toHaveCount(2)
        ->and($copies[0])->toEqual(new CarbonCopy('Finance', 'department', 4))
        ->and($copies[1]->type)->toBeNull()
        ->and($copies[1]->id)->toBeNull();
});

test('a new letter has no carbon copies and no extra values', function (): void {
    $letter = $this->permit->letters()->create(['type' => 'notice'])->fresh();

    expect($letter->carbon_copies)->toBe([])
        ->and($letter->extra)->toBe([])
        ->and($letter->with_letterhead)->toBeFalse()
        ->and($letter->computer_signed)->toBeFalse();
});

test('the letter type comes from the registry', function (): void {
    $letter = $this->permit->letters()->create(['type' => 'notice']);

    expect($letter->letter_type)->toBeInstanceOf(NoticeLetter::class);
});

test('extra values round-trip through the database', function (): void {
    $letter = $this->permit->letters()->create(['type' => 'notice', 'extra' => ['remark' => 'Urgent']]);

    expect($letter->fresh()->extra)->toBe(['remark' => 'Urgent']);
});

test('the signature is the sign action', function (): void {
    $letter = $this->permit->letters()->create(['type' => 'notice']);
    $profile = Profile::factory()->create();

    expect($letter->signature)->toBeNull();

    Actor::act($letter, Act::Sign, $profile->user);

    expect($letter->fresh()->signature?->name)->toBe(Act::Sign)
        ->and($letter->fresh()->signature?->actor_id)->toBe($profile->user_id);
});

test('a soft-deleted letter keeps its row and its source', function (): void {
    $letter = $this->permit->letters()->create(['type' => 'notice']);

    $letter->delete();

    expect(Letter::query()->count())->toBe(0)
        ->and(Letter::withTrashed()->count())->toBe(1);

    $this->permit->delete();

    expect(Letter::withTrashed()->first()->source->is($this->permit))->toBeTrue();
});

test('a reference number is unique within its sequence key while the letter is not deleted', function (): void {
    $attributes = ['type' => 'notice', 'sequence_key' => 'A', 'reference_number' => 'REF/1'];
    $first = $this->permit->letters()->create($attributes);

    expect(fn () => $this->permit->letters()->create($attributes))->toThrow(QueryException::class);

    $this->permit->letters()->create([...$attributes, 'sequence_key' => 'B']);

    $first->delete();

    expect($this->permit->letters()->create($attributes))->toBeInstanceOf(Letter::class);
});

test('two letters without a sequence key cannot share a reference number', function (): void {
    $attributes = ['type' => 'notice', 'reference_number' => 'REF/1'];
    $this->permit->letters()->create($attributes);

    expect(fn () => $this->permit->letters()->create($attributes))->toThrow(QueryException::class);
});

test('a carbon copy that is not a carbon copy is refused', function (mixed $copy): void {
    expect(fn () => $this->permit->letters()->create(['type' => 'notice', 'carbon_copies' => [$copy]]))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'a string' => ['Finance'],
    'an array without a name' => [['type' => 'department']],
    'an array with a non-string name' => [['name' => 5]],
    'an array with a non-string type' => [['name' => 'Finance', 'type' => 5]],
    'an array with a non-int id' => [['name' => 'Finance', 'type' => 'department', 'id' => '5']],
]);

test('a carbon copy array is stored as a carbon copy', function (): void {
    $letter = $this->permit->letters()->create(['type' => 'notice', 'carbon_copies' => [['name' => 'Finance']]]);

    expect($letter->fresh()->carbon_copies)->toEqual([new CarbonCopy('Finance')]);
});

test('the factory makes a letter of the registered type', function (): void {
    expect(Letter::factory()->create()->type)->toBe('notice');
    expect(Letter::factory()->ofType('other')->make()->type)->toBe('other');
});

test('the factory asks for a registered letter type', function (): void {
    config(['system.letters.types' => []]);

    expect(fn () => Letter::factory()->make())->toThrow(LogicException::class);
});
