<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use LogicException;

class SaveDepartment
{
    use Actionable;

    public function execute(Organization $organization, Department $department, string $code, string $name): Department
    {
        throw_if($department->is_owner, LogicException::class, 'The owner department cannot be changed here.');

        $department->organization()->associate($organization);
        $department->fill(['code' => $code, 'name' => $name])->save();

        return $department;
    }
}
