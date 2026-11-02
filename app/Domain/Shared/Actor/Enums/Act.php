<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actor\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithLabel;
use App\Domain\Shared\Common\Contracts\Enums\HasLabel;

enum Act: string implements HasLabel
{
    use EnumExtension, InteractsWithLabel;

    case Prepare = 'prepare';

    public function label(): string
    {
        return (string) __("common/acts.$this->value.label");
    }

    public function byLabel(): string
    {
        return (string) __("common/acts.$this->value.by");
    }
}
