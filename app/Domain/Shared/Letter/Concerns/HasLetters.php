<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Concerns;

use App\Domain\Shared\Letter\Models\Letter;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasLetters
{
    /**
     * @return MorphMany<Letter, $this>
     */
    public function letters(): MorphMany
    {
        return $this->morphMany(Letter::class, 'source');
    }
}
