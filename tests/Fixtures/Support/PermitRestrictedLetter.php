<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\User\Models\User;

class PermitRestrictedLetter extends PermitLetter
{
    public function allowedFor(User $printer): bool
    {
        return false;
    }
}
