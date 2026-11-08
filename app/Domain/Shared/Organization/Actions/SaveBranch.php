<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Organization;
use LogicException;

class SaveBranch
{
    use Actionable;

    public function execute(Organization $organization, Branch $branch, string $code, string $name): Branch
    {
        throw_if($branch->is_owner, LogicException::class, 'The owner branch cannot be changed here.');

        $branch->organization()->associate($organization);
        $branch->fill(['code' => $code, 'name' => $name])->save();

        return $branch;
    }
}
