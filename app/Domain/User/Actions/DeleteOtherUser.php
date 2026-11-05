<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
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

        $user->delete();
    }
}
