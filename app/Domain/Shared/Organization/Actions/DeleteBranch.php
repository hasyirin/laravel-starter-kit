<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Support\PlacedUsers;
use LogicException;

class DeleteBranch
{
    use Actionable;

    public function execute(Branch $branch): void
    {
        throw_if(PlacedUsers::of($branch)->exists(), LogicException::class, 'A branch with placed users cannot be deleted.');

        $branch->delete();
    }
}
