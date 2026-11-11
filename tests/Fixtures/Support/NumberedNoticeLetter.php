<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Enums\NumberingMode;
use App\Domain\Shared\Letter\Models\Letter;

class NumberedNoticeLetter extends NoticeLetter
{
    public function numbering(): NumberingMode
    {
        return NumberingMode::Automatic;
    }

    public function reference(int $number, Letter $letter): string
    {
        return "N/{$number}";
    }
}
