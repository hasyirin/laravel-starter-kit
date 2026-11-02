<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;

class CreateUser
{
    use Actionable;

    public function __construct(private readonly ChangeUserProfile $changeUserProfile) {}

    /**
     * @param  array{organization_id: ?int, branch_id: ?int, department_id: ?int, position_id: ?int}  $placement
     */
    public function execute(User $actor, string $name, string $email, string $password, array $placement): User
    {
        return (new User)->getConnection()->transaction(function () use ($actor, $name, $email, $password, $placement): User {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $this->changeUserProfile->execute($actor, $user, $placement);

            return $user;
        });
    }
}
