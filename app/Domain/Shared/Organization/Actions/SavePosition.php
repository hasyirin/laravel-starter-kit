<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;

class SavePosition
{
    use Actionable;

    public function execute(Organization $organization, Position $position, ?Department $department, string $name): Position
    {
        $position->organization()->associate($organization);
        $position->department()->associate($department);
        $position->fill(['name' => $name])->save();

        return $position;
    }
}
