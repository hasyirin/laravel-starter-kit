<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Organization\Support\PlacedUsers;
use LogicException;

class DeleteDepartment
{
    use Actionable;

    public function execute(Department $department): void
    {
        throw_if(PlacedUsers::of($department)->exists(), LogicException::class, 'A department with placed users cannot be deleted.');

        $department->getConnection()->transaction(function () use ($department): void {
            $department->positions->each(fn (Position $position): ?bool => $position->delete());
            $department->delete();
        });
    }
}
