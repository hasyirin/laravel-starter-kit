<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\User\Models\User;

class RestrictedNoticeLetter extends NoticeLetter
{
    public function allowedFor(User $printer): bool
    {
        return false;
    }
}
