<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Organization\Support\PlacedUsers;
use LogicException;

class DeleteOrganization
{
    use Actionable;

    public function execute(Organization $organization): void
    {
        throw_if(PlacedUsers::of($organization)->exists(), LogicException::class, 'An organization with placed users cannot be deleted.');

        $organization->getConnection()->transaction(function () use ($organization): void {
            $organization->positions->each(fn (Position $position): ?bool => $position->delete());
            $organization->departments->each(fn (Department $department): ?bool => $department->delete());
            $organization->branches->each(fn (Branch $branch): ?bool => $branch->delete());
            $organization->delete();
        });
    }
}
