<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;

class UpdateUserInformation
{
    use Actionable;

    public function execute(User $user, string $name, string $email, ?string $phone): void
    {
        $user->getConnection()->transaction(function () use ($user, $name, $email, $phone): void {
            $user->newQueryWithoutRelationships()->whereKey($user->getKey())->lockForUpdate()->first();
            $user->refresh();

            $user->fill([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            if ($user->isDirty('phone')) {
                $user->phone_verified_at = null;
            }

            $user->save();
        });
    }
}
