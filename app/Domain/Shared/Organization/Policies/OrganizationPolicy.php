<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Policies;

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\User\Models\User;

class OrganizationPolicy extends Policy
{
    protected static string $prefix = 'internal.controls';

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view-any');
    }

    public function view(User $user, Organization $organization, ?string $type = null): bool
    {
        return $this->hasAnyPermission($user, 'view-any', 'view') && $this->hasTypedPermission($user, 'view', $type);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create');
    }

    public function update(User $user, Organization $organization): bool
    {
        return ! $organization->is_owner && $this->hasPermission($user, 'update');
    }

    public function delete(User $user, Organization $organization): bool
    {
        return ! $organization->is_owner && $this->hasPermission($user, 'delete');
    }
}
