<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Security;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use SensitiveParameter;

class UpdatePassword
{
    use Actionable;

    public function execute(User $user, #[SensitiveParameter] string $password): void
    {
        $user->update([
            'password' => $password,
        ]);
    }
}
