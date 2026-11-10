<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

class PrintedNoticeLetter extends ExtraNoticeLetter
{
    public function view(): string
    {
        return 'tests::letters.printed';
    }

    public function keepsPrints(): bool
    {
        return true;
    }
}
