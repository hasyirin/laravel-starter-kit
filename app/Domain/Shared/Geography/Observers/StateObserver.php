<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Observers;

use App\Domain\Shared\Geography\Models\State;
use LogicException;

class StateObserver
{
    public function updating(State $state): void
    {
        throw_if($state->isDirty('code') && $state->holdsLocality(), LogicException::class, 'The code of a state that is or contains the locality cannot change.');
    }

    public function deleting(State $state): void
    {
        throw_if($state->holdsLocality(), LogicException::class, 'A state that is or contains the locality cannot be deleted.');
    }
}
