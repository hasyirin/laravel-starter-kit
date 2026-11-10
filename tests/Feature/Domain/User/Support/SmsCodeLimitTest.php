<?php

declare(strict_types=1);

use App\Domain\User\Exceptions\SmsCodeThrottled;
use App\Domain\User\Support\SmsCodeLimit;

test('each key allows one sms code per minute', function (): void {
    SmsCodeLimit::attempt('a');

    expect(fn () => SmsCodeLimit::attempt('a'))->toThrow(SmsCodeThrottled::class)
        ->and(fn () => SmsCodeLimit::attempt('b'))->not->toThrow(SmsCodeThrottled::class);

    $this->travel(60)->seconds();

    expect(fn () => SmsCodeLimit::attempt('a'))->not->toThrow(SmsCodeThrottled::class);
});

test('each key allows five sms codes per hour', function (): void {
    foreach (range(1, 5) as $sms) {
        SmsCodeLimit::attempt('a');
        $this->travel(60)->seconds();
    }

    expect(fn () => SmsCodeLimit::attempt('a'))
        ->toThrow(function (SmsCodeThrottled $e): void {
            expect($e->seconds)->toBeGreaterThan(60);
        });
});

test('a throttled attempt counts against no key', function (): void {
    SmsCodeLimit::attempt('phone-a', 'ip');

    expect(fn () => SmsCodeLimit::attempt('phone-b', 'ip'))->toThrow(SmsCodeThrottled::class)
        ->and(fn () => SmsCodeLimit::attempt('phone-b'))->not->toThrow(SmsCodeThrottled::class);
});
