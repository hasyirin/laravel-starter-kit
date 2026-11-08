<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Support;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PlacedUsers
{
    /**
     * @return Builder<User>
     */
    public static function of(Organization|Department|Branch|Position $record): Builder
    {
        return User::query()->whereHas('profile', function (Builder $profile) use ($record): void {
            match (true) {
                $record instanceof Organization => $profile->where('organization_id', $record->id),
                $record instanceof Branch => $profile->where('branch_id', $record->id),
                $record instanceof Position => $profile->where('position_id', $record->id),
                $record instanceof Department => $profile->where(fn (Builder $placement): Builder => $placement
                    ->where('department_id', $record->id)
                    ->orWhereIn('position_id', $record->positions()->withTrashed()->select('id'))),
            };
        });
    }
}
