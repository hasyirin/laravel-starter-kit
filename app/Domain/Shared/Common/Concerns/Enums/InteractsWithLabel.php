<?php

namespace App\Domain\Shared\Common\Concerns\Enums;

use Illuminate\Contracts\Support\Htmlable;

/**
 * @method Htmlable|string label()
 */
trait InteractsWithLabel
{
    public function getLabel(): Htmlable|string
    {
        return $this->label();
    }
}
