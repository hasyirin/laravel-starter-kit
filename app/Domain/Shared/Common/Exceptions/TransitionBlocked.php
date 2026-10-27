<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Exceptions;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\Exceptions\TransitionNotAllowed;
use Throwable;

class TransitionBlocked extends TransitionNotAllowed
{
    public string $reason = '';

    public static function because(Model $model, string $transitionClass, string $reason, ?Throwable $previous = null): self
    {
        $exception = new self(sprintf('The transition `%s` is not allowed on model `%s`: %s', $transitionClass, $model::class, $reason), previous: $previous);
        $exception->reason = $reason;
        $exception->setTransitionClass($transitionClass);

        return $exception;
    }
}
