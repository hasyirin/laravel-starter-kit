<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Transitions;

use App\Domain\Shared\Common\Exceptions\StaleState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\ModelStates\DefaultTransition;

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
        return DB::transaction(function (): Model {
            $current = $this->model->newQueryWithoutScopes()->lockForUpdate()->whereKey($this->model->getKey())->firstOrFail();

            if ($current->getRawOriginal($this->field) !== $this->model->getRawOriginal($this->field)) {
                throw StaleState::make($this->model, $this->field);
            }

            $this->model->setAttribute($this->field, $this->newState);
            $this->apply();
            $this->model->save();

            return $this->model;
        });
    }

    protected function apply(): void {}
}
