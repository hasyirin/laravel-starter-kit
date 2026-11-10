<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Enums;

enum LetterheadImage: string
{
    case Header = 'header';
    case Footer = 'footer';
}
