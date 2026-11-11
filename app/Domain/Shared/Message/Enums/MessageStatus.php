<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithColor;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithIcon;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithLabel;
use App\Domain\Shared\Common\Contracts\Enums\HasColor;
use App\Domain\Shared\Common\Contracts\Enums\HasIcon;
use App\Domain\Shared\Common\Contracts\Enums\HasLabel;
use Filament\Support\Icons\Heroicon;

enum MessageStatus: string implements HasColor, HasIcon, HasLabel
{
    use EnumExtension, InteractsWithColor, InteractsWithIcon, InteractsWithLabel;

    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return (string) __("message/messages.statuses.$this->value");
    }

    public function color(): string
    {
        return match ($this) {
            self::Sending => 'amber',
            self::Sent => 'green',
            self::Failed => 'red',
        };
    }

    public function icon(): Heroicon
    {
        return match ($this) {
            self::Sending => Heroicon::OutlinedClock,
            self::Sent => Heroicon::OutlinedCheckCircle,
            self::Failed => Heroicon::OutlinedXCircle,
        };
    }
}
