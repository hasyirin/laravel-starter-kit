<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Settings;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Actions\Session\EndAllSessions;
use App\Domain\User\Models\User;

class DeleteUser
{
    use Actionable;

    public function execute(User $user): void
    {
        $user->getConnection()->transaction(function () use ($user): void {
            $user->delete();

            EndAllSessions::make()->execute($user, endedBy: $user, includeImpersonated: true);
        });
    }
}
