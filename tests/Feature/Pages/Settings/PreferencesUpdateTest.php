<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Livewire\Settings\Preferences;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

test('the preferences page is displayed', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get('/settings/preferences')->assertOk();
});

test('the time zone and locale start at the ones of the user, or else at the config', function (?string $timezone, string $expected): void {
    config(['system.timezone' => 'Asia/Kuala_Lumpur']);

    $this->actingAs(User::factory()->create(['timezone' => $timezone]));

    Livewire::test(Preferences::class)->assertSet('timezone', $expected);
})->with([
    'a chosen time zone' => ['Asia/Tokyo', 'Asia/Tokyo'],
    'no time zone yet' => [null, 'Asia/Kuala_Lumpur'],
]);

test('the time zone and locale can be updated', function (): void {
    $user = User::factory()->create(['timezone' => 'Asia/Kuala_Lumpur', 'locale' => 'en-MY']);

    $this->actingAs($user);

    Livewire::test(Preferences::class)
        ->set('timezone', 'Asia/Tokyo')
        ->set('locale', 'en-GB')
        ->call('save')
        ->assertHasNoErrors();

    expect([$user->refresh()->timezone, $user->locale])->toBe(['Asia/Tokyo', 'en-GB']);
});

test('an unknown time zone or locale is rejected', function (string $field, string $value): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(Preferences::class)
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors([$field]);
})->with([
    'time zone' => ['timezone', 'Mars/Olympus'],
    'locale' => ['locale', 'fr-FR'],
]);

test('the preferences page shows how dates look with the chosen time zone and locale', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 06:30', 'UTC'));
    $this->actingAs(User::factory()->create());

    Livewire::test(Preferences::class)
        ->set('timezone', 'Asia/Kuala_Lumpur')
        ->set('locale', 'en-GB')
        ->assertSee('Dates and times look like this: 26 Oct 2026, 14:30');
});

test('the date and time formats are named in the language of the app', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(Preferences::class)
        ->assertSee('Date and time format')
        ->assertSee('Malay (Malaysia)')
        ->assertDontSee('Melayu (Malaysia)');
});
