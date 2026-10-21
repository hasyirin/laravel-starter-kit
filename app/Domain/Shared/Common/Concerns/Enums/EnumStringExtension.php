<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Concerns\Enums;

use Illuminate\Support\Stringable;

trait EnumStringExtension
{
    public function str(): Stringable
    {
        return str((string) $this->value);
    }

    public function camel(): string
    {
        return $this->str()->camel()->value();
    }

    public function studly(): string
    {
        return $this->str()->studly()->value();
    }

    public function headline(): string
    {
        return $this->str()->headline()->value();
    }

    public function plural(int $count = 2): string
    {
        return $this->str()->plural($count)->value();
    }

    public function snake(): string
    {
        return $this->str()->camel()->snake()->value();
    }
}
