<?php

declare(strict_types=1);

use App\Livewire\Pages\Local\Examples\Address;
use App\Livewire\Pages\Local\Examples\DistrictsMode;
use App\Livewire\Pages\Local\Examples\LocationMode;
use Illuminate\View\ViewException;
use Livewire\Livewire;

test('the districts mode comes as the text of a tag or as an enum case', function (DistrictsMode|string $districts, bool $required): void {
    Livewire::test(Address::class, ['districts' => $districts])
        ->assertSet('address.showingDistricts', true)
        ->assertSet('address.districtsRequired', $required);
})->with([
    'optional as text' => ['optional', false],
    'required as text' => ['required', true],
    'an enum case' => [DistrictsMode::Required, true],
]);

test('an unknown districts mode fails at once', function (): void {
    expect(fn () => Livewire::test(Address::class, ['districts' => 'requried']))
        ->toThrow(ViewException::class, '"requried" is not a valid backing value for enum');
});

test('the districts mode stays an enum case on the next request', function (): void {
    Livewire::test(Address::class, ['districts' => 'required'])
        ->call('save')
        ->assertSet('districts', DistrictsMode::Required);
});

test('the location mode comes as the text of a tag or as an enum case', function (LocationMode|string $location, bool $required): void {
    Livewire::test(Address::class, ['location' => $location])
        ->assertSet('address.showingLocation', true)
        ->assertSet('address.locationRequired', $required);
})->with([
    'optional as text' => ['optional', false],
    'required as text' => ['required', true],
    'an enum case' => [LocationMode::Required, true],
]);
