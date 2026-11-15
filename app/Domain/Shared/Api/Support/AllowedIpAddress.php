<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Support;

class AllowedIpAddress
{
    public static function valid(string $value): bool
    {
        $parts = str($value)->explode('/');

        if ($parts->count() > 2) {
            return false;
        }

        $address = $parts[0];
        $prefix = $parts[1] ?? null;

        if (filter_var($address, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if ($prefix === null) {
            return true;
        }

        $maximum = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false ? 128 : 32;

        return ctype_digit($prefix) && (int) $prefix >= 1 && (int) $prefix <= $maximum;
    }
}
