<?php

declare(strict_types=1);

namespace Tests\Fixtures\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithColor;
use App\Domain\Shared\Common\Contracts\Enums\HasColor;
use Filament\Support\Colors\Color;

enum Priority: int implements HasColor
{
    use EnumExtension, InteractsWithColor;

    case Low = 1;
    case High = 2;

    /**
     * @return string|array<string>
     */
    public function color(): array|string
    {
        return match ($this) {
            self::Low => Color::Blue,
            self::High => 'red',
        };
    }
}
