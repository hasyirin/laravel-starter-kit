<?php

declare(strict_types=1);

namespace Tests\Fixtures\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithSequence;

enum Status: string
{
    use EnumExtension, InteractsWithSequence;

    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';
}
