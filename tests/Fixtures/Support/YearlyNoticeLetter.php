<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Models\Letter;
use Hasyirin\RunningNumber\Enums\ResetInterval;

class YearlyNoticeLetter extends NumberedNoticeLetter
{
    public function sequenceReset(): ResetInterval
    {
        return ResetInterval::YEARLY;
    }

    public function reference(int $number, Letter $letter): string
    {
        return "N/{$number}/{$letter->date?->year}";
    }
}
