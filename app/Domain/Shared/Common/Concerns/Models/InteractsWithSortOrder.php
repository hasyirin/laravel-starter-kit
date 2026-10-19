<?php

namespace App\Domain\Shared\Common\Concerns\Models;

use Illuminate\Database\Eloquent\Builder;

trait InteractsWithSortOrder
{
    public static function bootInteractsWithSortOrder(): void
    {
        static::creating(function (self $model): void {
            $model->sort_order ??= $model->nextSortOrder();
        });
    }

    protected function nextSortOrder(): int
    {
        return ((int) $this->sortOrderScope(static::query())->max('sort_order')) + 1;
    }

    /**
     * Limits the max(sort_order) lookup to sibling rows. The default is the whole table.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function sortOrderScope(Builder $query): Builder
    {
        return $query;
    }
}
