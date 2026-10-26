<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use Carbon\CarbonImmutable;

test('a date and time and a time are formatted in the time zone and locale', function (string $timezone, string $locale, string $datetime, string $time): void {
    $preferences = new Preferences($timezone, $locale);
    $instant = CarbonImmutable::parse('2026-10-26 06:30', 'UTC');

    expect($preferences->datetime($instant))->toBe($datetime)
        ->and($preferences->time($instant))->toBe($time);
})->with([
    'English in Malaysia' => ['Asia/Kuala_Lumpur', 'en-MY', "26 Oct 2026, 2:30\u{202F}pm", "2:30\u{202F}pm"],
    'English in the United Kingdom' => ['Asia/Kuala_Lumpur', 'en-GB', '26 Oct 2026, 14:30', '14:30'],
    'Malay in Malaysia' => ['Asia/Kuala_Lumpur', 'ms-MY', "26 Okt 2026, 2:30\u{202F}PTG", "2:30\u{202F}PTG"],
    'English in the United States' => ['America/New_York', 'en-US', "Oct 26, 2026, 2:30\u{202F}AM", "2:30\u{202F}AM"],
]);

test('the date of a moment is the day on the clock of the user', function (): void {
    $preferences = new Preferences('Asia/Kuala_Lumpur', 'en-MY');

    expect($preferences->date(CarbonImmutable::parse('2026-10-26 20:00', 'UTC')))->toBe('27 Oct 2026');
});

test('a calendar date keeps its own day in a time zone behind UTC', function (): void {
    $preferences = new Preferences('America/New_York', 'en-MY');

    expect($preferences->calendarDate(CarbonImmutable::parse('2026-10-26', 'UTC')))->toBe('26 Oct 2026');
});

test('a calendar date with a fixed offset, as PostgreSQL returns it, keeps the day of its offset', function (string $value, string $day): void {
    $preferences = new Preferences('Asia/Kuala_Lumpur', 'en-MY');

    expect($preferences->calendarDate(CarbonImmutable::parse($value)))->toBe($day);
})->with([
    'ahead of UTC' => ['2026-10-26 01:00:00+08:00', '26 Oct 2026'],
    'behind UTC' => ['2026-10-25 23:30:00-05:00', '25 Oct 2026'],
]);

test('local moves an instant to the time zone of the user', function (): void {
    $local = new Preferences('Asia/Kuala_Lumpur', 'en-MY')->local(CarbonImmutable::parse('2026-10-26 20:00', 'UTC'));

    expect($local->format('Y-m-d H:i e'))->toBe('2026-10-27 04:00 Asia/Kuala_Lumpur');
});

test('a missing value is formatted as null', function (): void {
    $preferences = new Preferences('Asia/Kuala_Lumpur', 'en-MY');

    expect([$preferences->date(null), $preferences->calendarDate(null), $preferences->datetime(null), $preferences->time(null)])->toBe([null, null, null, null]);
});

test('the current preferences come from the request, or else from the config', function (): void {
    config(['system.timezone' => 'Asia/Kuala_Lumpur', 'system.locale' => 'en-MY']);

    $before = Preferences::current();
    context()->addHidden(['timezone' => 'Asia/Tokyo', 'locale' => 'en-GB']);
    $after = Preferences::current();

    expect([$before->timezone, $before->locale, $after->timezone, $after->locale])->toBe(['Asia/Kuala_Lumpur', 'en-MY', 'Asia/Tokyo', 'en-GB']);
});

test('the preferences of a user fall back to the config', function (): void {
    config(['system.locale' => 'en-MY']);

    $preferences = Preferences::of(User::factory()->make(['timezone' => 'Asia/Tokyo']));

    expect([$preferences->timezone, $preferences->locale])->toBe(['Asia/Tokyo', 'en-MY']);
});

test('times follow the time zone data of PHP, also where ICU has older data', function (string $timezone, string $datetime): void {
    expect(new Preferences($timezone, 'en-GB')->datetime(CarbonImmutable::parse('2026-10-26 06:30', 'UTC')))->toBe($datetime);
})->with([
    'a zone ICU does not know' => ['America/Coyhaique', '26 Oct 2026, 03:30'],
    'a zone whose offset changed' => ['Africa/Casablanca', '26 Oct 2026, 06:30'],
]);
