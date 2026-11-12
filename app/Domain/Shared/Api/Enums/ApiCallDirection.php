<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithLabel;
use App\Domain\Shared\Common\Contracts\Enums\HasLabel;

enum ApiCallDirection: string implements HasLabel
{
    use EnumExtension, InteractsWithLabel;

    case Inbound = 'inbound';
    case Outbound = 'outbound';

    public function label(): string
    {
        return (string) __("api/api-calls.directions.$this->value");
    }
}
