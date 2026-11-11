<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Concerns\Models;

trait HoldsLocality
{
    public function holdsLocality(): bool
    {
        return $this->is(static::local());
    }
}
