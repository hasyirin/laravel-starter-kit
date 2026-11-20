<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Support;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModelKey
{
    public static function valid(Model $model, mixed $value): bool
    {
        if ($model->getKeyType() === 'int') {
            return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
        }

        if (! in_array($model->getKeyName(), $model->uniqueIds(), true)) {
            return true;
        }

        $traits = class_uses_recursive($model);

        if (in_array(HasUuids::class, $traits, true)) {
            return is_string($value) && str($value)->isUuid();
        }

        if (in_array(HasUlids::class, $traits, true)) {
            return is_string($value) && str($value)->isUlid();
        }

        return true;
    }
}
