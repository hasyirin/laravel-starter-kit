<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Observers;

use App\Domain\Shared\Organization\Models\Department;
use LogicException;

class DepartmentObserver
{
    public function updating(Department $department): void
    {
        throw_if($department->isDirty('organization_id'), LogicException::class, 'The organization of a department cannot be changed.');
    }

    public function saved(): void
    {
        Department::forgetOwner();
    }

    public function deleting(Department $department): void
    {
        throw_if($department->is_owner, LogicException::class, 'The owner department cannot be deleted.');
    }

    public function deleted(): void
    {
        Department::forgetOwner();
    }

    public function restored(): void
    {
        Department::forgetOwner();
    }
}
