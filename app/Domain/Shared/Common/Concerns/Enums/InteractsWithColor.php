<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Concerns\Enums;

use Filament\Support\Colors\Color;

/**
 * @method string|array<string> color()
 */
trait InteractsWithColor
{
    public function getColor(): array|string
    {
        $color = $this->color();

        // gray is also a Filament theme color, so keep the name and let the app's theme apply.
        if (is_array($color) || $color === 'gray') {
            return $color;
        }

        return Color::all()[$color] ?? $color;
    }
}
