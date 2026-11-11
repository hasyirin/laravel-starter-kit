<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Enums;

enum NumberingMode: string
{
    case Manual = 'manual';
    case Automatic = 'automatic';
    case AutomaticWithOverride = 'automatic-with-override';
}
