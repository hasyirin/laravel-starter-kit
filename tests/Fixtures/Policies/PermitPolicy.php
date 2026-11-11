<?php

declare(strict_types=1);

namespace Tests\Fixtures\Policies;

use App\Domain\User\Models\User;
use Tests\Fixtures\Models\Permit;

class PermitPolicy
{
    public function view(User $user, Permit $permit): bool
    {
        return ! $permit->hidden;
    }

    public function update(User $user, Permit $permit): bool
    {
        return ! $permit->locked;
    }

    public function archive(User $user, Permit $permit): bool
    {
        return $permit->name === 'Archivable';
    }
}
