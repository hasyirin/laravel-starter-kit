<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Contracts\Enums;

use Illuminate\Contracts\Support\Htmlable;

interface HasLabel extends \Filament\Support\Contracts\HasLabel
{
    public function label(): Htmlable|string;
}
