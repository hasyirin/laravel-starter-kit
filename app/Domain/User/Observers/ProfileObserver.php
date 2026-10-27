<?php

declare(strict_types=1);

namespace App\Domain\User\Observers;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\Profile;
use LogicException;

class ProfileObserver
{
    public function creating(Profile $profile): void
    {
        throw_if($profile->department_id !== null && Department::query()->whereKey($profile->department_id)->where('organization_id', $profile->organization_id)->doesntExist(), LogicException::class, 'The department of a profile must belong to its organization.');

        throw_if($profile->branch_id !== null && Branch::query()->whereKey($profile->branch_id)->where('organization_id', $profile->organization_id)->doesntExist(), LogicException::class, 'The branch of a profile must belong to its organization.');

        throw_if($profile->position_id !== null && Position::query()->whereKey($profile->position_id)->where('organization_id', $profile->organization_id)->where('department_id', $profile->department_id)->doesntExist(), LogicException::class, 'The position of a profile must belong to its organization and department.');
    }

    public function updating(): void
    {
        throw new LogicException('Profiles cannot be updated. Create a new profile instead.');
    }
}
