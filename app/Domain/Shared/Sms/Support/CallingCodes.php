<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Support;

use SensitiveParameter;

final class CallingCodes
{
    public static function allows(#[SensitiveParameter] string $phone): bool
    {
        /** @var list<string> $codes */
        $codes = config()->array('system.sms.calling_codes');

        return str($phone)->startsWith($codes);
    }
}
