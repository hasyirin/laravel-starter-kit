<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Enums\NumberingMode;

class PermitOverrideLetter extends PermitLetter
{
    public function numbering(): NumberingMode
    {
        return NumberingMode::AutomaticWithOverride;
    }
}
