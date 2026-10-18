<?php

namespace App\Domain\Shared\Common\Concerns\Enums;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

trait EnumStringExtension
{
    public function str(): Stringable
    {
        return str((string) $this->value);
    }

    public function camel(): string
    {
        return Str::camel((string) $this->value);
    }

    public function studly(): string
    {
        return Str::studly((string) $this->value);
    }

    public function headline(): string
    {
        return Str::headline((string) $this->value);
    }

    public function plural(int $count = 2): string
    {
        return Str::plural((string) $this->value, $count);
    }

    public function snake(): string
    {
        return Str::snake($this->camel());
    }
}
