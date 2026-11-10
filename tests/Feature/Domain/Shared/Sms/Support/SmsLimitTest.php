<?php

declare(strict_types=1);

use App\Domain\Shared\Sms\Exceptions\SmsThrottled;
use App\Domain\Shared\Sms\Support\SmsLimit;

test('each key allows one sms per minute', function (): void {
    SmsLimit::attempt('a');

    expect(fn () => SmsLimit::attempt('a'))->toThrow(SmsThrottled::class)
        ->and(fn () => SmsLimit::attempt('b'))->not->toThrow(SmsThrottled::class);

    $this->travel(60)->seconds();

    expect(fn () => SmsLimit::attempt('a'))->not->toThrow(SmsThrottled::class);
});

test('each key allows five sms per hour', function (): void {
    foreach (range(1, 5) as $sms) {
        SmsLimit::attempt('a');
        $this->travel(60)->seconds();
    }

    expect(fn () => SmsLimit::attempt('a'))
        ->toThrow(function (SmsThrottled $e): void {
            expect($e->seconds)->toBeGreaterThan(60);
        });
});

test('a throttled attempt counts against no key', function (): void {
    SmsLimit::attempt('phone-a', 'ip');

    expect(fn () => SmsLimit::attempt('phone-b', 'ip'))->toThrow(SmsThrottled::class)
        ->and(fn () => SmsLimit::attempt('phone-b'))->not->toThrow(SmsThrottled::class);
});
