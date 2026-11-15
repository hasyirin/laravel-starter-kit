<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Policies;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\User\Models\User;

class ApiClientPolicy extends Policy
{
    protected static string $prefix = 'system';

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view-any');
    }

    public function view(User $user, ApiClient $apiClient, ?string $type = null): bool
    {
        return $this->hasAnyPermission($user, 'view-any', 'view') && $this->hasTypedPermission($user, 'view', $type);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create');
    }

    public function update(User $user, ApiClient $apiClient): bool
    {
        return $this->hasPermission($user, 'update');
    }

    public function delete(User $user, ApiClient $apiClient): bool
    {
        return $this->hasPermission($user, 'delete');
    }
}
