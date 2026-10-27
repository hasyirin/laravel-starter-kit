<?php

declare(strict_types=1);

namespace Tests\Fixtures\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithColor;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithIcon;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithLabel;
use App\Domain\Shared\Common\Contracts\Enums\HasColor;
use App\Domain\Shared\Common\Contracts\Enums\HasIcon;
use App\Domain\Shared\Common\Contracts\Enums\HasLabel;
use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

enum Plan: string implements HasColor, HasIcon, HasLabel
{
    use EnumExtension, InteractsWithColor, InteractsWithIcon, InteractsWithLabel;

    case Free = 'free';
    case Pro = 'pro';
    case Team = 'team';

    public function label(): Htmlable|string
    {
        return match ($this) {
            self::Free => 'Free plan',
            self::Pro => 'Pro plan',
            self::Team => new HtmlString('<strong>Team</strong> plan'),
        };
    }

    public function icon(): BackedEnum|Htmlable|string
    {
        return match ($this) {
            self::Free => 'gift',
            self::Pro => Heroicon::Star,
            self::Team => new HtmlString('<svg data-test="team-icon"></svg>'),
        };
    }

    /**
     * @return string|array<string>
     */
    public function color(): array|string
    {
        return match ($this) {
            self::Free => 'zinc',
            self::Pro => 'amber',
            self::Team => Color::Blue,
        };
    }
}
