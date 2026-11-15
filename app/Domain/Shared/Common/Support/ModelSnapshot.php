<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Support;

use Illuminate\Database\Eloquent\Model;
use WeakMap;

final class ModelSnapshot
{
    /**
     * @var WeakMap<Model, int>|null
     */
    private static ?WeakMap $pending = null;

    public static function restoreOnRollBack(Model $model): void
    {
        $position = (self::pending()[$model] ?? 0) + 1;
        self::pending()[$model] = $position;

        $attributes = $model->getAttributes();
        $original = $model->getRawOriginal();
        $relations = $model->getRelations();
        $exists = $model->exists;
        $recentlyCreated = $model->wasRecentlyCreated;

        // Laravel runs rollback callbacks oldest first. The earliest snapshot must win, so a later one skips.
        $model->getConnection()->afterRollBack(function () use ($model, $position, $attributes, $original, $relations, $exists, $recentlyCreated): void {
            if ((self::pending()[$model] ?? 0) < $position) {
                return;
            }

            self::pending()[$model] = $position - 1;

            $model->setRawAttributes($original)->syncOriginal()->setRawAttributes($attributes);
            $model->setRelations($relations);
            $model->exists = $exists;
            $model->wasRecentlyCreated = $recentlyCreated;
        });
    }

    /**
     * @return WeakMap<Model, int>
     */
    private static function pending(): WeakMap
    {
        return self::$pending ??= new WeakMap;
    }
}
