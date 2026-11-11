<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeleteCountry;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Organization\Models\Organization;

beforeEach(function (): void {
    config()->set('address.locality', ['country' => 'XXX', 'state' => null, 'district' => null]);
});

test('deleting a country that nothing uses soft-deletes only that country', function (): void {
    $country = Country::factory()->create();

    DeleteCountry::make()->execute($country);

    expect($country->fresh()->trashed())->toBeTrue()
        ->and(Country::withTrashed()->count())->toBe(1);
});

test('a country with a state cannot be deleted', function (): void {
    $country = State::factory()->create()->country;

    expect(fn () => DeleteCountry::make()->execute($country))->toThrow(LogicException::class, 'A country that is or contains the locality, has states, or has addresses cannot be deleted.')
        ->and($country->fresh()->trashed())->toBeFalse();
});

test('a country that an address uses cannot be deleted', function (): void {
    $country = Country::factory()->create();
    Address::factory()->for(Organization::factory(), 'addressable')->create(['country_id' => $country->id]);

    expect(fn () => DeleteCountry::make()->execute($country))->toThrow(LogicException::class)
        ->and($country->fresh()->trashed())->toBeFalse();
});

test('the local country cannot be deleted', function (): void {
    $country = Country::factory()->create(['code' => 'MYS']);
    config()->set('address.locality', ['country' => 'MYS', 'state' => null, 'district' => null]);

    expect(fn () => DeleteCountry::make()->execute($country))->toThrow(LogicException::class)
        ->and($country->fresh()->trashed())->toBeFalse();
});
