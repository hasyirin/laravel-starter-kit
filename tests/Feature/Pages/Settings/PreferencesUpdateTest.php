<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use App\Livewire\Settings\Preferences;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

test('the preferences page is displayed', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get('/settings/preferences')->assertOk();
});

test('a user with an unverified email can open the preferences page', function (): void {
    $this->actingAs(User::factory()->unverified()->create());

    $this->get(route('preferences.edit'))->assertOk();
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

test('the language starts at the one of the user, or else at the one of the app', function (?string $language, string $expected): void {
    app()->setLocale('ms');
    $this->actingAs(User::factory()->create(['language' => $language]));

    Livewire::test(Preferences::class)->assertSet('language', $expected);
})->with([
    'a chosen language' => ['ms', 'ms'],
    'no language yet' => [null, 'en'],
]);

test('a save during impersonation never gives the language of the impersonator to a user without one', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    Impersonation::begin(User::factory()->create(['language' => 'ms']), route('dashboard'));
    app()->setLocale('ms');

    Livewire::test(Preferences::class)
        ->set('timezone', 'Asia/Tokyo')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->refresh()->language)->toBe('en');
});

test('a user whose language is no longer listed can still save', function (): void {
    config(['system.languages' => ['en' => 'English']]);
    $user = User::factory()->create(['language' => 'ms']);

    $this->actingAs($user);

    Livewire::test(Preferences::class)
        ->set('timezone', 'Asia/Tokyo')
        ->call('save')
        ->assertHasNoErrors();

    expect([$user->refresh()->timezone, $user->language])->toBe(['Asia/Tokyo', 'en']);
});

test('the language can be updated, and the page loads again in that language', function (): void {
    $user = User::factory()->create(['language' => 'en']);

    $this->actingAs($user);

    Livewire::test(Preferences::class)
        ->set('language', 'ms')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('preferences.edit'));

    expect($user->refresh()->language)->toBe('ms')
        ->and(cookie()->queued('language')?->getValue())->toBe('ms');
});

test('a save that keeps the language does not load the page again', function (): void {
    $this->actingAs(User::factory()->create(['language' => 'en']));

    Livewire::test(Preferences::class)
        ->set('timezone', 'Asia/Tokyo')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNoRedirect();

    expect(cookie()->queued('language')?->getValue())->toBe('en');
});

test('an unlisted language is rejected', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(Preferences::class)
        ->set('language', 'fr')
        ->call('save')
        ->assertHasErrors(['language']);
});

test('each language is named in its own language', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(Preferences::class)
        ->assertSeeInOrder(['wire:model="language"', 'English', 'Bahasa Melayu']);
});

test('the language select is hidden when the app lists one language', function (): void {
    config(['system.languages' => ['en' => 'English']]);
    $this->actingAs(User::factory()->create());

    Livewire::test(Preferences::class)->assertDontSeeHtml('wire:model="language"');
});
