<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Enums;

enum LetterMedia: string
{
    case Prints = 'prints';
    case SignedScan = 'signed-scan';
    case Data = 'data';
}
