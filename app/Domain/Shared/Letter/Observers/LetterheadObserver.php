<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Observers;

use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use LogicException;

class LetterheadObserver
{
    public function saving(Letterhead $letterhead): void
    {
        throw_if($letterhead->branch_id !== null && Branch::query()->whereKey($letterhead->branch_id)->where('organization_id', $letterhead->organization_id)->doesntExist(), LogicException::class, 'The branch of a letterhead must belong to its organization.');

        throw_if($letterhead->department_id !== null && Department::query()->whereKey($letterhead->department_id)->where('organization_id', $letterhead->organization_id)->doesntExist(), LogicException::class, 'The department of a letterhead must belong to its organization.');
    }
}
