<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Enums\NumberingMode;

class PermitNumberedLetter extends PermitPrintLetter
{
    public function numbering(): NumberingMode
    {
        return NumberingMode::Automatic;
    }
}
