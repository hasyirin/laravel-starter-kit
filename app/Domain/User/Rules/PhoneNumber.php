<?php

declare(strict_types=1);

namespace App\Domain\User\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use SensitiveParameter;

class PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, #[SensitiveParameter] mixed $value, Closure $fail): void
    {
        if (is_string($value) && str($value)->isMatch('/^\+[1-9]\d{6,14}\z/')) {
            return;
        }

        $fail('validation.phone_number')->translate();
    }
}
