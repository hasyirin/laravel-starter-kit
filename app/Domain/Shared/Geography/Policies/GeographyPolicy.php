<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Policies;

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class GeographyPolicy extends Policy
{
    protected static string $prefix = 'internal.controls';

    protected static ?string $segment = 'geography';

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view-any');
    }

    public function view(User $user, Model $record, ?string $type = null): bool
    {
        return $this->hasAnyPermission($user, 'view-any', 'view') && $this->hasTypedPermission($user, 'view', $type);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create');
    }

    public function update(User $user, Model $record): bool
    {
        return $this->hasPermission($user, 'update');
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->hasPermission($user, 'delete');
    }
}
