<?php

declare(strict_types=1);

namespace App\Domain\User\Concerns;

use App\Domain\User\Models\Profile;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasProfile
{
    public function initializeHasProfile(): void
    {
        $this->with[] = 'profile';
    }

    /**
     * @return HasOne<Profile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class)->latestOfMany();
    }

    /**
     * @return HasMany<Profile, $this>
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }
}
