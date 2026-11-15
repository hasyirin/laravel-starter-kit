<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Shared\Common\Support\ModelSnapshot;
use App\Livewire\Concerns\InteractsWithAbilities;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

abstract class Form extends \Livewire\Form
{
    use AuthorizesRequests;
    use InteractsWithAbilities;

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    protected function transaction(Closure $callback): mixed
    {
        return $this->mapErrors(fn (): mixed => DB::transaction(function () use ($callback): mixed {
            collect(get_object_vars($this))
                ->filter(fn (mixed $value): bool => $value instanceof Model)
                ->each(fn (Model $model) => ModelSnapshot::restoreOnRollBack($model));

            return $callback();
        }));
    }

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    protected function mapErrors(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (ValidationException $validationException) {
            $messages = collect($validationException->errors())->mapWithKeys(fn (mixed $errors, int|string $key): array => [
                $this->getPropertyName().'.'.$this->errorKey((string) $key) => $errors,
            ]);

            throw ValidationException::withMessages($messages->all());
        }
    }

    protected function errorKey(string $key): string
    {
        return $key;
    }
}
