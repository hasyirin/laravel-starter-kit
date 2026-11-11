<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\SaveCountry;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\State;

test('saving creates and updates a country, also its code', function (): void {
    $country = SaveCountry::make()->execute(new Country, 'SGP', 'Singapore', 'SG');

    expect($country->exists)->toBeTrue();

    SaveCountry::make()->execute($country, 'SIN', 'Republic of Singapore', null);

    expect($country->fresh()->only('code', 'name', 'alpha_2'))->toBe(['code' => 'SIN', 'name' => 'Republic of Singapore', 'alpha_2' => null]);
});

test('creating a country with the code of a trashed country restores that country', function (): void {
    $trashed = Country::factory()->create(['code' => 'SGP', 'name' => 'Old']);
    $trashed->delete();

    $country = SaveCountry::make()->execute(new Country, 'SGP', 'Singapore', 'SG');

    expect($country->id)->toBe($trashed->id)
        ->and($country->fresh()->trashed())->toBeFalse()
        ->and($country->fresh()->only('name', 'alpha_2'))->toBe(['name' => 'Singapore', 'alpha_2' => 'SG']);
});

test('the code of a country that is or contains the locality cannot change', function (array $locality): void {
    $country = Country::factory()->create(['code' => 'MYS']);
    State::factory()->for($country)->create(['code' => '01']);
    config()->set('address.locality', $locality);

    expect(fn (): Country => SaveCountry::make()->execute($country, 'MAS', 'Malaysia', null))->toThrow(LogicException::class)
        ->and($country->fresh()->code)->toBe('MYS');
})->with([
    'the local country' => [['country' => 'MYS', 'state' => null, 'district' => null]],
    'the country of a local state' => [['country' => 'MYS', 'state' => '01', 'district' => null]],
]);

test('the locality keeps its code but can change its name', function (): void {
    $country = Country::factory()->create(['code' => 'MYS', 'name' => 'Malaysia']);
    config()->set('address.locality', ['country' => 'MYS', 'state' => null, 'district' => null]);

    SaveCountry::make()->execute($country, 'MYS', 'Persekutuan Malaysia', 'MY');

    expect($country->fresh()->only('code', 'name'))->toBe(['code' => 'MYS', 'name' => 'Persekutuan Malaysia']);
});
