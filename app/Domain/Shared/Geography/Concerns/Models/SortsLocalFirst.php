<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Concerns\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait SortsLocalFirst
{
    /**
     * @return Builder<static>
     */
    abstract public static function localRows(): Builder;

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function localFirst(Builder $query): void
    {
        $local = $query->getQuery()->newQuery()
            ->fromSub(static::localRows()->select($this->getKeyName()), 'local_rows')
            ->selectRaw('count(*)')
            ->whereColumn("local_rows.{$this->getKeyName()}", $this->getQualifiedKeyName());

        $query->orderByDesc($local);
    }
}
