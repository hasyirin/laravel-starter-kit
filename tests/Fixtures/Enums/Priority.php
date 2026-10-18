<?php

declare(strict_types=1);

namespace Tests\Fixtures\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;

enum Priority: int
{
    use EnumExtension;

    case Low = 1;
    case High = 2;
}
