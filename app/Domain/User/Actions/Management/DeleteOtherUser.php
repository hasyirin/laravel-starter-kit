<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Management;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Actions\Session\EndAllSessions;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteOtherUser
{
    use Actionable;

    /**
     * @throws AuthorizationException
     */
    public function execute(User $user, ?User $actor = null): void
    {
        $actor ??= auth()->user();

        throw_if(! $actor instanceof User || $actor->is($user), AuthorizationException::class);

        $bypassPermission = config()->string('system.permissions.bypass-authorization');

        throw_if($user->checkPermissionTo($bypassPermission) && ! $actor->checkPermissionTo($bypassPermission), AuthorizationException::class);

        $user->getConnection()->transaction(function () use ($user): void {
            $user->delete();

            EndAllSessions::make()->execute($user, endedBy: Impersonation::personAtBrowser(), includeImpersonated: true);
        });
    }
}
