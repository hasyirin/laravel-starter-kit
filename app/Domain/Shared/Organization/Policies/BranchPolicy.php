<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Policies;

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\User\Models\User;

class BranchPolicy extends Policy
{
    protected static string $prefix = 'internal.controls';

    protected static ?string $model = Organization::class;

    public function view(User $user, Branch $branch, ?string $type = null): bool
    {
        return $this->hasAnyPermission($user, 'view-any', 'view') && $this->hasTypedPermission($user, 'view', $type);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'update');
    }

    public function update(User $user, Branch $branch): bool
    {
        return ! $branch->is_owner && $this->hasPermission($user, 'update');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return ! $branch->is_owner && $this->hasPermission($user, 'update');
    }
}
