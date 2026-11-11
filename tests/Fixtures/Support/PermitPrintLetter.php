<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

class PermitPrintLetter extends PermitLetter
{
    public function view(): string
    {
        return 'tests::letters.permit';
    }
}
