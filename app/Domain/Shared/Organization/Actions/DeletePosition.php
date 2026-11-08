<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Organization\Support\PlacedUsers;
use LogicException;

class DeletePosition
{
    use Actionable;

    public function execute(Position $position): void
    {
        throw_if(PlacedUsers::of($position)->exists(), LogicException::class, 'A position with placed users cannot be deleted.');

        $position->delete();
    }
}
