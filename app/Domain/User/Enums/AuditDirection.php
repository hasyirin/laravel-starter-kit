<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithLabel;
use App\Domain\Shared\Common\Contracts\Enums\HasLabel;

enum AuditDirection: string implements HasLabel
{
    use EnumExtension, InteractsWithLabel;

    case By = 'by';
    case On = 'on';

    public function label(): string
    {
        return (string) __("user/users.audit.$this->value");
    }
}
