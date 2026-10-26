<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Transitions;

use App\Domain\Shared\Common\Exceptions\StaleState;
use App\Domain\Shared\Common\Exceptions\StateNotSaved;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\DefaultTransition;
use Throwable;

/**
 * @template TModel of Model
 */
class Transition extends DefaultTransition
{
    /**
     * @var TModel
     */
    protected $model;

    public static function ability(): string
    {
        return str(class_basename(static::class))->kebab()->value();
    }

    public function reason(): ?string
    {
        return null;
    }

    public function canTransition(): bool
    {
        return $this->reason() === null;
    }

    /**
     * @return TModel
     */
    public function handle(): Model
    {
        $attributes = $this->model->getAttributes();

        try {
            return $this->model->getConnection()->transaction(function (): Model {
                if ($this->model->exists) {
                    $current = $this->model->newQueryWithoutScopes()->lockForUpdate()->whereKey($this->model->getKey())->first();

                    if ($current === null || $current->getRawOriginal($this->field) !== $this->model->getRawOriginal($this->field)) {
                        throw StaleState::make($this->model, $this->field);
                    }
                }

                $this->model->setAttribute($this->field, $this->newState);
                $this->apply();

                if (! $this->model->save()) {
                    throw StateNotSaved::make($this->model, $this->field);
                }

                return $this->model;
            });
        } catch (Throwable $throwable) {
            $this->model->setRawAttributes($attributes);

            throw $throwable;
        }
    }

    protected function apply(): void {}
}
