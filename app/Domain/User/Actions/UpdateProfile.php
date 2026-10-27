<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;

class UpdateProfile
{
    use Actionable;

    public function execute(User $user, string $name, string $email, string $timezone, string $locale): void
    {
        $user->fill([
            'name' => $name,
            'email' => $email,
            'timezone' => $timezone,
            'locale' => $locale,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
