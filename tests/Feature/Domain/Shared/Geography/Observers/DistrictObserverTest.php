<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\State;

beforeEach(function (): void {
    config()->set('address.locality', ['country' => 'MYS', 'state' => '01', 'district' => '0101']);
    $this->johor = State::factory()->for(Country::factory()->create(['code' => 'MYS']))->create(['code' => '01']);
});

test('the local district cannot be deleted, also outside the delete action', function (): void {
    $district = District::factory()->for($this->johor)->create(['code' => '0101']);

    expect(fn () => $district->delete())->toThrow(LogicException::class, 'A district that is or contains the locality cannot be deleted.')
        ->and($district->fresh()->trashed())->toBeFalse();
});

test('the code of the local district cannot change, also outside the save action', function (): void {
    $district = District::factory()->for($this->johor)->create(['code' => '0101']);

    expect(fn () => $district->update(['code' => '9999']))->toThrow(LogicException::class, 'The code of a district that is or contains the locality cannot change.')
        ->and($district->fresh()->code)->toBe('0101');
});

test('a district outside the locality can take the locality code', function (): void {
    $district = District::factory()->for($this->johor)->create(['code' => '0102']);

    $district->update(['code' => '0101']);

    expect($district->fresh()->code)->toBe('0101');
});
