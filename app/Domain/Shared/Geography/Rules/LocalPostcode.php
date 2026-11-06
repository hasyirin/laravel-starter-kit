<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Rules;

use App\Domain\Shared\Geography\Models\PostOffice;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

class LocalPostcode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $local = PostOffice::query()
            ->local()
            ->whereHas('postcodes', fn (Builder $query) => $query->where('code', $value))
            ->exists();

        if ($local) {
            return;
        }

        $fail((string) __('common/inputs.postcode_outside_local_area'));
    }
}
