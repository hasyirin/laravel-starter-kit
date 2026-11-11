<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Support;

use App\Domain\Shared\Message\Support\Intercept;

class SmsIntercept
{
    public static function enabled(): bool
    {
        return Intercept::enabled('system.sms.intercept.enabled');
    }

    public static function phone(): ?string
    {
        return Intercept::user()->phone ?? (config()->string('system.sms.intercept.phone') ?: null);
    }
}
