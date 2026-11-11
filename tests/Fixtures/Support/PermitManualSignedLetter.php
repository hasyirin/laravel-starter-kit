<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Enums\SignerRequirement;

class PermitManualSignedLetter extends PermitManualLetter
{
    public function signer(): SignerRequirement
    {
        return SignerRequirement::Required;
    }
}
