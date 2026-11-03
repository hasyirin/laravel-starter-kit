<?php

declare(strict_types=1);

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Override;

class ActColumn extends Column
{
    protected string $view = 'filament.tables.columns.act-column';

    /**
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     * @return Builder<Model>|Relation<Model, Model, mixed>
     */
    #[Override]
    public function applyEagerLoading(Builder|Relation $query): Builder|Relation
    {
        $name = $this->getName();

        return $query->with(["{$name}.profile.user", "{$name}.profile.position", "{$name}.actor"]);
    }
}
