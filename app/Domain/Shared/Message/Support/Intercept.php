<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Support;

use App\Domain\User\Models\User;

class Intercept
{
    public static function enabled(string $setting): bool
    {
        return ! app()->isProduction() && config()->boolean($setting);
    }

    public static function user(): ?User
    {
        // Read the signed-in user first. The context gets the ID of the impersonator when the guard finds that user.
        $user = auth()->user();
        $impersonatorId = context()->get('impersonator_id');

        if ($impersonatorId !== null) {
            return User::withTrashed()->whereKey($impersonatorId)->first();
        }

        if ($user instanceof User) {
            return $user;
        }

        return User::withTrashed()->whereKey(context()->get('user_id'))->first();
    }
}
