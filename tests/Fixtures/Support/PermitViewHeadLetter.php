<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Support\LetterheadView;
use LogicException;

class PermitViewHeadLetter extends PermitLetter
{
    public static ?LetterheadView $letterheadView = null;

    public function letterhead(): LetterheadView
    {
        return self::$letterheadView ?? throw new LogicException('Set the letterhead view first.');
    }
}
