<?php

namespace App\Domain\Shared\Common\Concerns\Enums;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @method BackedEnum|Htmlable|string icon()
 */
trait InteractsWithIcon
{
    public function getIcon(): BackedEnum|Htmlable|string
    {
        return $this->icon();
    }
}
