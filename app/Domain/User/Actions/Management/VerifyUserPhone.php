<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Management;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use SensitiveParameter;

class VerifyUserPhone
{
    use Actionable;

    /**
     * @throws AuthorizationException
     */
    public function execute(User $actor, User $user, #[SensitiveParameter] string $phone): bool
    {
        $bypassPermission = config()->string('system.permissions.bypass-authorization');

        throw_if($user->checkPermissionTo($bypassPermission) && ! $actor->checkPermissionTo($bypassPermission), AuthorizationException::class);

        return $user->getConnection()->transaction(function () use ($user, $phone): bool {
            $user->newQueryWithoutRelationships()->whereKey($user->getKey())->lockForUpdate()->first();
            $user->refresh();

            if ($user->phone !== $phone) {
                return false;
            }

            if (! $user->hasVerifiedPhone()) {
                $user->markPhoneAsVerified();
            }

            return true;
        });
    }
}
