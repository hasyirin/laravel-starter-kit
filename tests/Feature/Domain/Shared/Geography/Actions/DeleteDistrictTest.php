<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeleteDistrict;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Organization\Models\Organization;

beforeEach(function (): void {
    config()->set('address.locality', ['country' => 'XXX', 'state' => null, 'district' => null]);
});

test('deleting a district that nothing uses soft-deletes only that district', function (): void {
    $district = District::factory()->create();

    DeleteDistrict::make()->execute($district);

    expect($district->fresh()->trashed())->toBeTrue()
        ->and(District::withTrashed()->count())->toBe(1);
});

test('a district with a subdistrict cannot be deleted', function (): void {
    $district = Subdistrict::factory()->create()->district;

    expect(fn () => DeleteDistrict::make()->execute($district))->toThrow(LogicException::class, 'A district that is the locality, has subdistricts, or has addresses cannot be deleted.')
        ->and($district->fresh()->trashed())->toBeFalse();
});

test('a district that an address uses cannot be deleted', function (): void {
    $district = District::factory()->create();
    Address::factory()->for(Organization::factory(), 'addressable')->create(['district_id' => $district->id]);

    expect(fn () => DeleteDistrict::make()->execute($district))->toThrow(LogicException::class)
        ->and($district->fresh()->trashed())->toBeFalse();
});

test('the local district cannot be deleted', function (): void {
    $state = State::factory()->for(Country::factory()->create(['code' => 'MYS']))->create(['code' => '01']);
    $district = District::factory()->for($state)->create(['code' => '0101']);
    config()->set('address.locality', ['country' => 'MYS', 'state' => '01', 'district' => '0101']);

    expect(fn () => DeleteDistrict::make()->execute($district))->toThrow(LogicException::class)
        ->and($district->fresh()->trashed())->toBeFalse();
});
