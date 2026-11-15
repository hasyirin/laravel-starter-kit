<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Management;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Laravel\Passkeys\Actions\DeletePasskey;

class RevokeUserPasskey
{
    use Actionable;

    /**
     * @throws AuthorizationException
     */
    public function execute(User $actor, User $user, int $passkeyId): void
    {
        throw_if($actor->is($user), AuthorizationException::class);

        $bypassPermission = config()->string('system.permissions.bypass-authorization');

        throw_if($user->checkPermissionTo($bypassPermission) && ! $actor->checkPermissionTo($bypassPermission), AuthorizationException::class);

        resolve(DeletePasskey::class)($user, $user->passkeys()->findOrFail($passkeyId));
    }
}
