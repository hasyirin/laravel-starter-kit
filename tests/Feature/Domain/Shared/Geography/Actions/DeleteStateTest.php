<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeleteState;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Organization\Models\Organization;

beforeEach(function (): void {
    config()->set('address.locality', ['country' => 'XXX', 'state' => null, 'district' => null]);
});

test('deleting a state that nothing uses soft-deletes only that state', function (): void {
    $state = State::factory()->create();

    DeleteState::make()->execute($state);

    expect($state->fresh()->trashed())->toBeTrue()
        ->and(State::withTrashed()->count())->toBe(1);
});

test('a state with children cannot be deleted', function (string $child): void {
    $state = $child::factory()->create()->state;

    expect(fn () => DeleteState::make()->execute($state))->toThrow(LogicException::class, 'A state that is or contains the locality, has districts or cities, or has addresses cannot be deleted.')
        ->and($state->fresh()->trashed())->toBeFalse();
})->with([
    'a district' => [District::class],
    'a city' => [PostOffice::class],
]);

test('a state that an address uses cannot be deleted', function (): void {
    $state = State::factory()->create();
    Address::factory()->for(Organization::factory(), 'addressable')->create(['state_id' => $state->id]);

    expect(fn () => DeleteState::make()->execute($state))->toThrow(LogicException::class)
        ->and($state->fresh()->trashed())->toBeFalse();
});

test('a trashed address does not block a delete', function (): void {
    $state = State::factory()->create();
    Address::factory()->for(Organization::factory(), 'addressable')->create(['state_id' => $state->id])->delete();

    DeleteState::make()->execute($state);

    expect($state->fresh()->trashed())->toBeTrue();
});

test('the local state cannot be deleted', function (): void {
    $state = State::factory()->for(Country::factory()->create(['code' => 'MYS']))->create(['code' => '01']);
    config()->set('address.locality', ['country' => 'MYS', 'state' => '01', 'district' => null]);

    expect(fn () => DeleteState::make()->execute($state))->toThrow(LogicException::class)
        ->and($state->fresh()->trashed())->toBeFalse();
});

test('a state outside the locality can be deleted', function (): void {
    $country = Country::factory()->create(['code' => 'MYS']);
    State::factory()->for($country)->create(['code' => '01']);
    $other = State::factory()->for($country)->create(['code' => '02']);
    config()->set('address.locality', ['country' => 'MYS', 'state' => '01', 'district' => null]);

    DeleteState::make()->execute($other);

    expect($other->fresh()->trashed())->toBeTrue();
});
