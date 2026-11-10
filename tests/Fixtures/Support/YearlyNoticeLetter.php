<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Models\Letter;
use Carbon\CarbonImmutable;
use Hasyirin\RunningNumber\Enums\ResetInterval;

class YearlyNoticeLetter extends NumberedNoticeLetter
{
    public function sequenceReset(): ResetInterval
    {
        return ResetInterval::YEARLY;
    }

    public function reference(int $number, Letter $letter): string
    {
        $year = CarbonImmutable::now(config()->string('system.timezone'))->year;

        return "N/{$number}/{$year}";
    }
}
