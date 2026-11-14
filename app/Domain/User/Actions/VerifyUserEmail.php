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
    public function execute(User $actor, User $user, string $email): bool
    {
        $bypassPermission = config()->string('system.permissions.bypass-authorization');

        throw_if($user->checkPermissionTo($bypassPermission) && ! $actor->checkPermissionTo($bypassPermission), AuthorizationException::class);

        return $user->getConnection()->transaction(function () use ($user, $email): bool {
            $user->newQueryWithoutRelationships()->whereKey($user->getKey())->lockForUpdate()->first();
            $user->refresh();

            if ($user->email !== $email) {
                return false;
            }

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            return true;
        });
    }
}
