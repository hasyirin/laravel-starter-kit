<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Observers;

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Position;
use LogicException;

class PositionObserver
{
    public function saving(Position $position): void
    {
        if ($position->department_id === null || ! $position->isDirty(['organization_id', 'department_id'])) {
            return;
        }

        $organizationId = Department::withTrashed()->whereKey($position->department_id)->value('organization_id');

        throw_if((int) $organizationId !== (int) $position->organization_id, LogicException::class, 'The department of a position must belong to the same organization.');
    }

    public function updating(Position $position): void
    {
        throw_if($position->isDirty('organization_id'), LogicException::class, 'The organization of a position cannot be changed.');
    }
}
