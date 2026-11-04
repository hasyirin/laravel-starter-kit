<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CreateUser
{
    use Actionable;

    /**
     * @param  array{organization_id: ?int, branch_id: ?int, department_id: ?int, position_id: ?int}  $placement
     * @param  array<int, string>|null  $roles
     * @param  array<int, string>|null  $permissions
     *
     * @throws AuthorizationException
     */
    public function execute(
        User $actor,
        string $name,
        string $email,
        string $password,
        array $placement,
        ?array $roles = null,
        ?array $permissions = null,
    ): User {
        return (new User)->getConnection()->transaction(function () use ($actor, $name, $email, $password, $placement, $roles, $permissions): User {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            ChangeUserProfile::make()->execute($actor, $user, $placement, $roles, $permissions);

            return $user;
        });
    }
}
