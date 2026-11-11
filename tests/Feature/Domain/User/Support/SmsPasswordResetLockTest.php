<?php

declare(strict_types=1);

use App\Domain\User\Exceptions\SmsPasswordResetLocked;
use App\Domain\User\Support\SmsPasswordResetLock;
use Illuminate\Cache\Events\RetrievingKey;
use Illuminate\Support\Facades\Event;

test('a locked number waits at least one second, also in the last second of the lock', function (): void {
    $this->travelTo(now()->startOfSecond()->addMilliseconds(900));
    lockSmsPasswordReset('+60123456789');
    $this->travelTo(now()->addDay()->subMilliseconds(400));

    expect(fn () => SmsPasswordResetLock::ensureOpen('+60123456789'))
        ->toThrow(function (SmsPasswordResetLocked $e): void {
            expect($e->seconds)->toBe(1);
        });
});

test('the cache keys of the lock do not show the phone number', function (): void {
    $cacheKeys = collect();
    Event::listen(RetrievingKey::class, function (RetrievingKey $event) use ($cacheKeys): void {
        $cacheKeys->push($event->key);
    });

    SmsPasswordResetLock::countWrongCode('+60123456789');
    SmsPasswordResetLock::ensureOpen('+60123456789');

    expect($cacheKeys)->not->toBeEmpty()
        ->each->not->toContain('60123456789');
});
