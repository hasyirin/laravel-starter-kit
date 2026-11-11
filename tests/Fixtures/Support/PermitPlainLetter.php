<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

class PermitPlainLetter extends PermitLetter
{
    public function offersLetterhead(): bool
    {
        return false;
    }
}
