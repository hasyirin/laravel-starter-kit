<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;

class UpdateUser
{
    use Actionable;

    public function __construct(private readonly ChangeUserProfile $changeUserProfile) {}

    /**
     * @param  array{organization_id: ?int, branch_id: ?int, department_id: ?int, position_id: ?int}  $placement
     */
    public function execute(User $actor, User $user, string $name, string $email, ?string $password, array $placement): void
    {
        $user->getConnection()->transaction(function () use ($actor, $user, $name, $email, $password, $placement): void {
            $user->fill(['name' => $name, 'email' => $email]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            if ($password !== null) {
                $user->password = $password;
            }

            $user->save();

            $this->changeUserProfile->execute($actor, $user, $placement);
        });
    }
}
