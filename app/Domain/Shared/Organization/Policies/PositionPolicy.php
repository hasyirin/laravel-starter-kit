<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Policies;

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\User;

class PositionPolicy extends Policy
{
    protected static string $prefix = 'internal.controls';

    protected static ?string $model = Organization::class;

    public function view(User $user, Position $position): bool
    {
        return $this->hasAnyPermission($user, 'view-any', 'view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'update');
    }

    public function update(User $user, Position $position): bool
    {
        return $this->hasPermission($user, 'update');
    }

    public function delete(User $user, Position $position): bool
    {
        return $this->hasPermission($user, 'update');
    }
}
