<?php

declare(strict_types=1);

namespace Tests\Fixtures\Policies;

class ToggledLetterPolicy
{
    public static bool $update = true;

    public static bool $print = true;

    public function view(): bool
    {
        return true;
    }

    public function create(): bool
    {
        return true;
    }

    public function update(): bool
    {
        return self::$update;
    }

    public function print(): bool
    {
        return self::$print;
    }
}
