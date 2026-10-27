<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Exceptions;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

class StateNotSaved extends CouldNotPerformTransition
{
    public static function make(Model $model, string $field): self
    {
        return new self(sprintf('The %s of %s [%s] was not saved, because a listener stopped the save.', $field, $model::class, (string) $model->getKey()));
    }
}
