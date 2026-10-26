<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Spatie\Activitylog\Models\Activity;

test('a user without a time zone and locale gets the ones of their browser, without an activity log entry', function (): void {
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
