<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\SaveDistrict;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\State;

test('saving creates and updates a district under its state, also its code', function (): void {
    $first = State::factory()->create();
    $second = State::factory()->create();

    $district = SaveDistrict::make()->execute($first, new District, '01', 'Muar');

    expect($district->exists)->toBeTrue()
        ->and($district->state_id)->toBe($first->id);

    SaveDistrict::make()->execute($second, $district, '02', 'Baling');

    expect($district->fresh()->only('state_id', 'code', 'name'))->toBe(['state_id' => $second->id, 'code' => '02', 'name' => 'Baling']);
});

test('creating a district with the code of a trashed district of the same state restores that district', function (): void {
    $state = State::factory()->create();
    $trashed = District::factory()->for($state)->create(['code' => '01']);
    $trashed->delete();

    $district = SaveDistrict::make()->execute($state, new District, '01', 'Muar');

    expect($district->id)->toBe($trashed->id)
        ->and($district->fresh()->trashed())->toBeFalse()
        ->and(District::withTrashed()->count())->toBe(1);
});

test('the code of the local district cannot change', function (): void {
    $state = State::factory()->for(Country::factory()->create(['code' => 'MYS']))->create(['code' => '01']);
    $district = District::factory()->for($state)->create(['code' => '0101']);
    config()->set('address.locality', ['country' => 'MYS', 'state' => '01', 'district' => '0101']);

    expect(fn (): District => SaveDistrict::make()->execute($state, $district, '9999', 'Muar'))->toThrow(LogicException::class)
        ->and($district->fresh()->code)->toBe('0101');
});
