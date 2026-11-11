<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Support\DeleteBlocker;
use LogicException;

class DeleteState
{
    use Actionable;

    public function execute(State $state): void
    {
        throw_if(DeleteBlocker::blocks($state, State::local()), LogicException::class, 'A state that is or contains the locality, has districts or cities, or has addresses cannot be deleted.');

        $state->delete();
    }
}
