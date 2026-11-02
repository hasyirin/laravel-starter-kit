<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;

class VerifyUserEmail
{
    use Actionable;

    public function execute(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
    }
}
