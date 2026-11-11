<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Placement;
use Illuminate\Auth\Access\AuthorizationException;
use SensitiveParameter;

class CreateUser
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
        string $name,
        string $email,
        #[SensitiveParameter] ?string $phone,
        string $password,
        Placement $placement,
        ?array $roles = null,
        ?array $permissions = null,
    ): User {
        $user = (new User)->getConnection()->transaction(function () use ($actor, $name, $email, $phone, $password, $placement, $roles, $permissions): User {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
            ]);

            ChangeUserProfile::make()->execute(
                actor: $actor,
                user: $user,
                placement: $placement,
                roles: $roles,
                permissions: $permissions,
            );

            return $user;
        });

        $user->sendEmailVerificationNotification();

        return $user;
    }
}
