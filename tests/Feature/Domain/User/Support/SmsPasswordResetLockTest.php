<?php

declare(strict_types=1);

use App\Domain\User\Exceptions\SmsPasswordResetLocked;
use App\Domain\User\Support\SmsPasswordResetLock;

test('a locked number waits at least one second, also in the last second of the lock', function (): void {
    $this->travelTo(now()->startOfSecond()->addMilliseconds(900));
    lockSmsPasswordReset('+60123456789');
    $this->travelTo(now()->addDay()->subMilliseconds(400));

    expect(fn () => SmsPasswordResetLock::ensureOpen('+60123456789'))
        ->toThrow(function (SmsPasswordResetLocked $e): void {
            expect($e->seconds)->toBe(1);
        });
});
