<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Placement;
use Illuminate\Auth\Access\AuthorizationException;
use SensitiveParameter;

class UpdateUser
{
    use Actionable;

    /**
     * @param  array<int, string>|null  $roles
     * @param  array<int, string>|null  $permissions
     *
     * @throws AuthorizationException
     */
    public function execute(
        User $actor,
        User $user,
        string $name,
        string $email,
        #[SensitiveParameter] ?string $phone,
        #[SensitiveParameter] ?string $password,
        Placement $placement,
        ?array $roles = null,
        ?array $permissions = null,
    ): void {
        $user->getConnection()->transaction(function () use ($actor, $user, $name, $email, $phone, $password, $placement, $roles, $permissions): void {
            $user->newQueryWithoutRelationships()->whereKey($user->getKey())->lockForUpdate()->first();
            $user->refresh();

            $user->fill(['name' => $name, 'email' => $email, 'phone' => $phone]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            if ($user->isDirty('phone')) {
                $user->phone_verified_at = null;
            }

            if ($password !== null) {
                $user->password = $password;
            }

            $user->save();

            ChangeUserProfile::make()->execute(
                actor: $actor,
                user: $user,
                placement: $placement,
                roles: $roles,
                permissions: $permissions,
            );
        });
    }
}
