<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

class MalayNoticeLetter extends ExtraNoticeLetter
{
    public function view(): string
    {
        return 'tests::letters.printed';
    }

    public function locale(): string
    {
        return 'ms-MY';
    }
}
