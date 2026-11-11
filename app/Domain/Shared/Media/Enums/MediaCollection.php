<?php

declare(strict_types=1);

namespace App\Domain\Shared\Media\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;

enum MediaCollection: string
{
    use EnumExtension;

    case Attachments = 'attachments';
}
