<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Enums;

enum SignerRequirement: string
{
    case Required = 'required';
    case Optional = 'optional';
    case Hidden = 'hidden';
}
