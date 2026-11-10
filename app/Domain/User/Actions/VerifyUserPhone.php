<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class VerifyUserPhone
{
    use Actionable;

    /**
     * @throws AuthorizationException
     */
    public function execute(User $actor, User $user): void
    {
        $bypassPermission = config()->string('system.permissions.bypass-authorization');

        throw_if($user->checkPermissionTo($bypassPermission) && ! $actor->checkPermissionTo($bypassPermission), AuthorizationException::class);

        $phone = $user->phone;

        $user->getConnection()->transaction(function () use ($user, $phone): void {
            $user->newQueryWithoutRelationships()->whereKey($user->getKey())->lockForUpdate()->first();
            $user->refresh();

            if ($phone === null || $user->phone !== $phone || $user->hasVerifiedPhone()) {
                return;
            }

            $user->markPhoneAsVerified();
        });
    }
}
