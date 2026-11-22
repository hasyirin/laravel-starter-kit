<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Exceptions;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\DateProperties;

test('a date from the browser updates a date property', function (string $property, string $value, string $date): void {
    Livewire::test(DateProperties::class)
        ->set($property, $value)
        ->assertSet($property, CarbonImmutable::parse($date));
})->with([
    'an empty property' => ['startsOn'],
    'a set property' => ['endsOn'],
])->with([
    'a year of 9999' => ['9999-12-31', '9999-12-31'],
    'a year of 0' => ['0000-01-01', '0000-01-01'],
    'a negative year' => ['-0001-01-01', '-0001-01-01'],
]);

test('null or an empty string clears a date property', function (mixed $value): void {
    Livewire::test(DateProperties::class)
        ->set('endsOn', $value)
        ->assertSet('endsOn', null);
})->with([
    'null' => [null],
    'an empty string' => [''],
]);

test('a bad date value expires the page in production, is not reported, and the component keeps working', function (string $property, mixed $value): void {
    config(['app.debug' => false]);
    $component = Livewire::test(DateProperties::class);
    Exceptions::fake();

    $response = sendLivewireUpdate($component, [$property => $value]);

    $response->assertStatus(419);
    Exceptions::assertNothingReported();
    sendLivewireUpdate($component, [$property => '2026-01-01'])->assertOk();
})->with([
    'an empty property' => ['startsOn'],
    'a set property' => ['endsOn'],
    'a native date property' => ['heldOn'],
])->with([
    'a year above 9999' => ['+10000-01-01'],
    'text that is not a date' => ['abc'],
    'an array' => [['2026-01-01']],
]);

test('a bad date value throws its error in debug mode', function (string $property, mixed $value, string $error): void {
    config(['app.debug' => true]);

    $response = sendLivewireUpdate(Livewire::test(DateProperties::class), [$property => $value]);

    $response->assertServerError();

    expect($response->exception)->toBeInstanceOf($error);
})->with([
    'a year above 9999 for an empty property' => ['startsOn', '+10000-01-01', InvalidFormatException::class],
    'a year above 9999 for a set property' => ['endsOn', '+10000-01-01', InvalidFormatException::class],
    'text that is not a date for an empty property' => ['startsOn', 'abc', InvalidFormatException::class],
    'text that is not a date for a set property' => ['endsOn', 'abc', InvalidFormatException::class],
    'an array for a set property' => ['endsOn', ['2026-01-01'], TypeError::class],
    'a year above 9999 for a native date property' => ['heldOn', '+10000-01-01', DateMalformedStringException::class],
    'text that is not a date for a native date property' => ['heldOn', 'abc', DateMalformedStringException::class],
]);
