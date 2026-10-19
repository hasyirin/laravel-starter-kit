<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Observers;

use App\Domain\Shared\Organization\Models\Branch;
use LogicException;

class BranchObserver
{
    public function updating(Branch $branch): void
    {
        throw_if($branch->isDirty('organization_id'), LogicException::class, 'The organization of a branch cannot be changed.');
    }

    public function saved(): void
    {
        Branch::forgetOwner();
    }

    public function deleting(Branch $branch): void
    {
        throw_if($branch->is_owner, LogicException::class, 'The owner branch cannot be deleted.');
    }

    public function deleted(): void
    {
        Branch::forgetOwner();
    }

    public function restored(): void
    {
        Branch::forgetOwner();
    }
}
