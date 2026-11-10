<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Domain\User\Exceptions\SmsPasswordResetLocked;
use Illuminate\Support\Facades\RateLimiter;

final class SmsPasswordResetLock
{
    private const int WRONG_CODES = 10;

    private const int SECONDS = 86_400;

    /**
     * @throws SmsPasswordResetLocked
     */
    public static function ensureOpen(string $phone): void
    {
        $key = self::key($phone);

        if (! RateLimiter::tooManyAttempts($key, self::WRONG_CODES)) {
            return;
        }

        throw new SmsPasswordResetLocked(RateLimiter::availableIn($key));
    }

    public static function countWrongCode(string $phone): void
    {
        RateLimiter::hit(self::key($phone), self::SECONDS);
    }

    private static function key(string $phone): string
    {
        return "sms-password-reset-lock:{$phone}";
    }
}
