<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Contracts;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;

interface HasIcon extends \Filament\Support\Contracts\HasIcon
{
    public function icon(): BackedEnum|Htmlable|string;
}
