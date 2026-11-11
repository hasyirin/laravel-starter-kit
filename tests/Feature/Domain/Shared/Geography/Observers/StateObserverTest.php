<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\State;

beforeEach(function (): void {
    config()->set('address.locality', ['country' => 'MYS', 'state' => '01', 'district' => null]);
    $this->malaysia = Country::factory()->create(['code' => 'MYS']);
});

test('the local state cannot be deleted, also outside the delete action', function (): void {
    $state = State::factory()->for($this->malaysia)->create(['code' => '01']);

    expect(fn () => $state->delete())->toThrow(LogicException::class, 'A state that is or contains the locality cannot be deleted.')
        ->and($state->fresh()->trashed())->toBeFalse();
});

test('the code of the local state cannot change, also outside the save action', function (): void {
    $state = State::factory()->for($this->malaysia)->create(['code' => '01']);

    expect(fn () => $state->update(['code' => '99']))->toThrow(LogicException::class, 'The code of a state that is or contains the locality cannot change.')
        ->and($state->fresh()->code)->toBe('01');
});

test('a state outside the locality can take the locality code', function (): void {
    $state = State::factory()->for($this->malaysia)->create(['code' => '02']);

    $state->update(['code' => '01']);

    expect($state->fresh()->code)->toBe('01');
});
