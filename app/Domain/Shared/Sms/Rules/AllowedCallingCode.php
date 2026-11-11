<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Rules;

use App\Domain\Shared\Sms\Support\CallingCodes;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use SensitiveParameter;

class AllowedCallingCode implements ValidationRule
{
    public function validate(string $attribute, #[SensitiveParameter] mixed $value, Closure $fail): void
    {
        if (is_string($value) && CallingCodes::allows($value)) {
            return;
        }

        $fail('validation.calling_code')->translate();
    }
}
