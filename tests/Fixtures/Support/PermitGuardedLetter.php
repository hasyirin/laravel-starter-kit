<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Enums\SignerRequirement;

class PermitGuardedLetter extends PermitLetter
{
    public function signer(): SignerRequirement
    {
        return SignerRequirement::Optional;
    }

    public function offersComputerSigned(): bool
    {
        return true;
    }

    public function sourceAbility(string $letterAbility): string
    {
        return $letterAbility === 'print' ? 'archive' : parent::sourceAbility($letterAbility);
    }
}
