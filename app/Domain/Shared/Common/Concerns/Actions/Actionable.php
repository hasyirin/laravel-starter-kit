<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Concerns\Actions;

trait Actionable
{
    public static function make(): static
    {
        /** @var static $action */
        $action = resolve(static::class);

        return $action;
    }
}
