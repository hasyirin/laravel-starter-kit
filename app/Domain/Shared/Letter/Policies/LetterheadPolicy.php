<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Policies;

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\User\Models\User;

class LetterheadPolicy extends Policy
{
    protected static string $prefix = 'internal.controls';

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view-any');
    }

    public function view(User $user, Letterhead $letterhead, ?string $type = null): bool
    {
        return $this->hasAnyPermission($user, 'view-any', 'view') && $this->hasTypedPermission($user, 'view', $type);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create');
    }

    public function update(User $user, Letterhead $letterhead): bool
    {
        return $this->hasPermission($user, 'update');
    }

    public function delete(User $user, Letterhead $letterhead): bool
    {
        return $this->hasPermission($user, 'delete');
    }
}
