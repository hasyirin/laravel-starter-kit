<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\SaveState;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\State;

test('saving creates and updates a state under its country, also its code', function (): void {
    $first = Country::factory()->create();
    $second = Country::factory()->create();

    $state = SaveState::make()->execute($first, new State, '01', 'Johor');

    expect($state->exists)->toBeTrue()
        ->and($state->country_id)->toBe($first->id);

    SaveState::make()->execute($second, $state, '02', 'Kedah');

    expect($state->fresh()->only('country_id', 'code', 'name'))->toBe(['country_id' => $second->id, 'code' => '02', 'name' => 'Kedah']);
});

test('creating a state with the code of a trashed state of the same country restores that state', function (): void {
    $country = Country::factory()->create();
    $trashed = State::factory()->for($country)->create(['code' => '01']);
    $trashed->delete();

    $state = SaveState::make()->execute($country, new State, '01', 'Johor');

    expect($state->id)->toBe($trashed->id)
        ->and($state->fresh()->trashed())->toBeFalse()
        ->and(State::withTrashed()->count())->toBe(1);
});

test('creating a state with the code of a trashed state of another country creates a new state', function (): void {
    $trashed = State::factory()->create(['code' => '01']);
    $trashed->delete();

    $state = SaveState::make()->execute(Country::factory()->create(), new State, '01', 'Johor');

    expect($state->id)->not->toBe($trashed->id)
        ->and($trashed->fresh()->trashed())->toBeTrue();
});

test('the code of a state that is or contains the locality cannot change', function (array $locality): void {
    $country = Country::factory()->create(['code' => 'MYS']);
    $state = State::factory()->for($country)->create(['code' => '01']);
    District::factory()->for($state)->create(['code' => '0101']);
    config()->set('address.locality', $locality);

    expect(fn (): State => SaveState::make()->execute($country, $state, '99', 'Johor'))->toThrow(LogicException::class)
        ->and($state->fresh()->code)->toBe('01');
})->with([
    'the local state' => [['country' => 'MYS', 'state' => '01', 'district' => null]],
    'the state of a local district' => [['country' => 'MYS', 'state' => '01', 'district' => '0101']],
]);

test('a state outside the locality can change its code', function (): void {
    $country = Country::factory()->create(['code' => 'MYS']);
    $state = State::factory()->for($country)->create(['code' => '01']);
    config()->set('address.locality', ['country' => 'MYS', 'state' => '02', 'district' => null]);

    SaveState::make()->execute($country, $state, '03', 'Johor');

    expect($state->fresh()->code)->toBe('03');
});
