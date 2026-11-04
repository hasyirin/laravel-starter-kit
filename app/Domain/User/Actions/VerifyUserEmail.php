<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class VerifyUserEmail
{
    use Actionable;

    /**
     * @throws AuthorizationException
     */
    public function execute(User $actor, User $user): void
    {
        $bypassPermission = config()->string('system.permissions.bypass-authorization');

        throw_if($user->checkPermissionTo($bypassPermission) && ! $actor->checkPermissionTo($bypassPermission), AuthorizationException::class);

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->markEmailAsVerified();
    }
}
