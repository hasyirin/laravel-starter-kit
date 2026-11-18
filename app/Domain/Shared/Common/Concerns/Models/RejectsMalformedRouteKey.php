<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Concerns\Models;

use App\Domain\Shared\Common\Support\ModelKey;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Override;

trait RejectsMalformedRouteKey
{
    /**
     * @param  int|string  $value
     */
    #[Override]
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $column = $field ?? $this->getRouteKeyName();
        $bindsKey = in_array($column, [$this->getKeyName(), $this->getQualifiedKeyName()], true);

        if ($bindsKey && ! ModelKey::valid($this, $value)) {
            throw (new ModelNotFoundException)->setModel(static::class, $value);
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }
}
