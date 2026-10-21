<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Concerns\Enums;

/**
 * Neither end wraps: stepping past the first or last case returns null.
 */
trait InteractsWithSequence
{
    public function next(): ?self
    {
        $cases = self::cases();

        return $cases[array_search($this, $cases, true) + 1] ?? null;
    }

    public function previous(): ?self
    {
        $cases = self::cases();
        $index = array_search($this, $cases, true);

        return $index > 0 ? $cases[$index - 1] : null;
    }
}
