<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actor\Concerns;

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Actor\Models\Action;
use Hasyirin\Actor\Facades\Actor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait InteractsWithPreparer
{
    public static function bootInteractsWithPreparer(): void
    {
        static::created(function (Model $model): void {
            if (auth(config('actor.guard'))->check()) {
                Actor::act($model, Act::Prepare);
            }
        });
    }

    /**
     * @return MorphOne<Action, $this>
     */
    public function preparer(): MorphOne
    {
        return $this->morphOne(config('actor.models.action'), 'resource')->ofName(Act::Prepare);
    }
}
