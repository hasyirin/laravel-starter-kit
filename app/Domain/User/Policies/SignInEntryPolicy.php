<?php

declare(strict_types=1);

namespace App\Domain\User\Policies;

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\User\Models\User;

class SignInEntryPolicy extends Policy
{
    protected static string $prefix = 'system.logs';

    protected static ?string $segment = 'sign-ins';

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view');
    }
}
