<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AllowedIpAddress implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && $this->valid($value)) {
            return;
        }

        $fail('validation.allowed_ip_address')->translate();
    }

    private function valid(string $value): bool
    {
        $parts = explode('/', $value);

        if (count($parts) > 2) {
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

        return ctype_digit($prefix) && (int) $prefix <= $maximum;
    }
}
