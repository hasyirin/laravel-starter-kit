<?php

declare(strict_types=1);

namespace Tests\Fixtures\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithColor;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithSequence;
use App\Domain\Shared\Common\Contracts\HasColor;

enum Status: string implements HasColor
{
    use EnumExtension, InteractsWithColor, InteractsWithSequence;

    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::InReview => 'warning',
            self::Published => 'green',
        };
    }
}
