<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Exceptions;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

class StaleState extends CouldNotPerformTransition
{
    public static function make(Model $model, string $field): self
    {
        return new self(sprintf('The %s of %s [%s] changed, or the record was deleted, after it was loaded. Load it again and retry.', $field, $model::class, (string) $model->getKey()));
    }
}
