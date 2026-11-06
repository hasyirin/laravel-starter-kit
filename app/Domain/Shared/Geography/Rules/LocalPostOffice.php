<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Rules;

use App\Domain\Shared\Geography\Models\PostOffice;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LocalPostOffice implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $local = PostOffice::query()->local()->whereKey($value)->exists();

        if ($local) {
            return;
        }

        $fail((string) __('common/inputs.city_outside_local_area'));
    }
}
