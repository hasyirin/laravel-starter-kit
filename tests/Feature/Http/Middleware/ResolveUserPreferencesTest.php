<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use Spatie\Activitylog\Models\Activity;

test('a user without a time zone and locale gets the ones of their browser, without an audit entry', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->withUnencryptedCookies(['timezone' => 'Asia/Tokyo', 'locale' => 'en-GB,en'])->get(route('dashboard'))->assertOk();

    expect([$user->refresh()->timezone, $user->locale])->toBe(['Asia/Tokyo', 'en-GB'])
        ->and([context()->getHidden('timezone'), context()->getHidden('locale')])->toBe(['Asia/Tokyo', 'en-GB'])
        ->and(Activity::query()->where('event', 'updated')->exists())->toBeFalse();
});

test('a time zone and locale that the user has are kept', function (): void {
    $user = User::factory()->create(['timezone' => 'Asia/Kuala_Lumpur', 'locale' => 'ms-MY']);

    $this->actingAs($user)->withUnencryptedCookies(['timezone' => 'Asia/Tokyo', 'locale' => 'en-US'])->get(route('dashboard'))->assertOk();

    expect([$user->refresh()->timezone, $user->locale])->toBe(['Asia/Kuala_Lumpur', 'ms-MY'])
        ->and([context()->getHidden('timezone'), context()->getHidden('locale')])->toBe(['Asia/Kuala_Lumpur', 'ms-MY']);
});

test('the browser languages pick a supported locale, then a supported language', function (string $languages, ?string $locale): void {
    config(['system.locales' => ['en-MY', 'ms-MY', 'en-GB', 'en-US']]);
    $user = User::factory()->create();

    $this->actingAs($user)->withUnencryptedCookie('locale', $languages)->get(route('dashboard'))->assertOk();

    expect($user->refresh()->locale)->toBe($locale);
})->with([
    'a supported locale' => ['en-US,en', 'en-US'],
    'a later supported locale before an earlier language match' => ['en-SG,ms-MY', 'ms-MY'],
    'a supported language' => ['fr-FR,ms', 'ms-MY'],
    'nothing supported' => ['de-DE,fr', null],
]);

test('an old zone name from the browser is stored by its current name', function (string $browser, string $stored): void {
    $user = User::factory()->create();

    $this->actingAs($user)->withUnencryptedCookie('timezone', $browser)->get(route('dashboard'))->assertOk();

    expect($user->refresh()->timezone)->toBe($stored);
})->with([
    'India in Chrome' => ['Asia/Calcutta', 'Asia/Kolkata'],
    'Vietnam in Chrome' => ['Asia/Saigon', 'Asia/Ho_Chi_Minh'],
    'UTC' => ['UTC', 'UTC'],
]);

test('unknown browser values are ignored, and the config fills in', function (): void {
    config(['system.timezone' => 'Asia/Kuala_Lumpur', 'system.locale' => 'en-MY']);
    $user = User::factory()->create();

    $this->actingAs($user)->withUnencryptedCookies(['timezone' => 'Mars/Olympus', 'locale' => 'xx'])->get(route('dashboard'))->assertOk();

    expect([$user->refresh()->timezone, $user->locale])->toBe([null, null])
        ->and([context()->getHidden('timezone'), context()->getHidden('locale')])->toBe(['Asia/Kuala_Lumpur', 'en-MY']);
});

test('a guest gets the time zone and locale of the browser for the request', function (): void {
    $this->withUnencryptedCookies(['timezone' => 'Europe/London', 'locale' => 'en-GB'])->get(route('login'))->assertOk();

    expect([context()->getHidden('timezone'), context()->getHidden('locale')])->toBe(['Europe/London', 'en-GB']);
});

test('a user with a language gets that language, whatever the switch and the browser say', function (): void {
    $user = User::factory()->create(['language' => 'ms']);

    $this->actingAs($user)->withCookie('language', 'en')->withUnencryptedCookie('locale', 'en-US')->get(route('dashboard'))->assertOk();

    expect($user->refresh()->language)->toBe('ms')
        ->and(app()->getLocale())->toBe('ms');
});

test('a user without a language gets the choice of the switch, then the language of the browser, without an audit entry', function (?string $choice, string $browser, ?string $stored, string $shown): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withCookies(array_filter(['language' => $choice]))
        ->withUnencryptedCookie('locale', $browser)
        ->get(route('dashboard'))
        ->assertOk();

    expect($user->refresh()->language)->toBe($stored)
        ->and(app()->getLocale())->toBe($shown)
        ->and(Activity::query()->where('event', 'updated')->exists())->toBeFalse();
})->with([
    'the choice of the switch' => ['ms', 'en-US,en', 'ms', 'ms'],
    'the first supported language of the browser' => [null, 'fr-FR,ms-MY,en', 'ms', 'ms'],
    'an unknown choice' => ['fr', 'en-GB', 'en', 'en'],
    'nothing supported' => [null, 'de-DE,fr', null, 'en'],
]);

test('a guest gets the choice of the switch, then the language of the browser, then the language of the app', function (?string $choice, string $browser, string $shown): void {
    $this->withCookies(array_filter(['language' => $choice]))->withUnencryptedCookie('locale', $browser)->get(route('login'))->assertOk();

    expect(app()->getLocale())->toBe($shown);
})->with([
    'the choice of the switch' => ['ms', 'en-US', 'ms'],
    'the language of the browser' => [null, 'ms-MY,en', 'ms'],
    'the language of the app' => [null, 'de-DE', 'en'],
]);

test('a language that the app does not list is never used', function (): void {
    config(['system.languages' => ['en' => 'English']]);
    $user = User::factory()->create(['language' => 'ms']);

    $this->actingAs($user)->withCookie('language', 'ms')->withUnencryptedCookie('locale', 'ms-MY')->get(route('dashboard'))->assertOk();

    expect(app()->getLocale())->toBe('en');
});

test('the first listed language is used when the app does not list its own language', function (): void {
    config(['system.languages' => ['ms' => 'Bahasa Melayu'], 'system.language' => 'en']);

    $this->get(route('login'))->assertOk();

    expect(app()->getLocale())->toBe('ms');
});

test('impersonation never fills the empty preferences of the user from the browser of the impersonator', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    Impersonation::begin(User::factory()->create(), route('dashboard'));

    $this->withCookie('language', 'ms')->withUnencryptedCookies(['timezone' => 'Asia/Tokyo', 'locale' => 'ms-MY'])->get(route('dashboard'))->assertOk();

    expect([$user->refresh()->timezone, $user->language, $user->locale])->toBe([null, null, null]);
});

test('a page shows its text in the chosen language', function (string $language, string $heading): void {
    $this->withCookie('language', $language)->get(route('login'))->assertOk()->assertSee($heading)->assertSee('lang="'.$language.'"', false);
})->with([
    'English' => ['en', 'Log in to your account'],
    'Malay' => ['ms', 'Log masuk ke akaun anda'],
]);
