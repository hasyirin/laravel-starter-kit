<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Shared\Common\Support\ModelSnapshot;
use App\Livewire\Concerns\InteractsWithAbilities;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use WeakMap;

abstract class Form extends \Livewire\Form
{
    use AuthorizesRequests;
    use InteractsWithAbilities;

    /**
     * @var WeakMap<ValidationException, true>|null
     */
    private static ?WeakMap $prefixed = null;

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
            throw $this->prefixErrors($validationException);
        }
    }

    /**
     * @param  array<string, mixed>|null  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     * @return array<string, mixed>
     */
    public function validate($rules = null, $messages = [], $attributes = []): array
    {
        return $this->keepKeys(fn (): array => parent::validate($rules, $messages, $attributes));
    }

    /**
     * @param  string  $field
     * @param  array<string, mixed>|null  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     * @param  array<string, mixed>  $dataOverrides
     * @return array<string, mixed>
     */
    public function validateOnly($field, $rules = null, $messages = [], $attributes = [], $dataOverrides = []): array
    {
        return $this->keepKeys(fn (): array => parent::validateOnly($field, $rules, $messages, $attributes, $dataOverrides));
    }

    protected function errorKey(string $key): string
    {
        return $key;
    }

    /**
     * @param  Closure(): array<string, mixed>  $validation
     * @return array<string, mixed>
     */
    private function keepKeys(Closure $validation): array
    {
        try {
            return $validation();
        } catch (ValidationException $validationException) {
            $this->prefixed()[$validationException] = true;

            throw $validationException;
        }
    }

    private function prefixErrors(ValidationException $validationException): ValidationException
    {
        if (isset($this->prefixed()[$validationException])) {
            return $validationException;
        }

        $validator = $validationException->validator;

        $messages = collect($validator->errors()->messages())
            ->mapToGroups(fn (array $errors, int|string $key): array => [$this->formKey($key) => $errors])
            ->map(fn (Collection $errors): array => array_merge(...$errors->all()))
            ->all();
        $failedRules = collect($validator->failed())
            ->mapToGroups(fn (mixed $rules, int|string $key): array => [$this->formKey($key) => $rules])
            ->map(fn (Collection $rules): array => $rules->collapse()->all())
            ->all();

        $mappedValidator = $validator instanceof Validator ? $validator : validator([], []);

        // The validator has no setters. Livewire's own Form::validate() changes the same properties, so the test helpers still see the failed rules.
        (function () use ($messages, $failedRules): void {
            $this->messages = new MessageBag($messages);
            $this->failedRules = $failedRules;
        })->call($mappedValidator);

        $validationException->validator = $mappedValidator;
        $this->prefixed()[$validationException] = true;

        return $validationException;
    }

    private function formKey(int|string $key): string
    {
        return $this->getPropertyName().'.'.$this->errorKey((string) $key);
    }

    /**
     * @return WeakMap<ValidationException, true>
     */
    private function prefixed(): WeakMap
    {
        return self::$prefixed ??= new WeakMap;
    }
}
