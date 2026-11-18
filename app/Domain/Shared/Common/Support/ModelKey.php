<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Support;

use Illuminate\Database\Eloquent\Model;

class ModelKey
{
    public static function valid(Model $model, mixed $value): bool
    {
        if ($model->getKeyType() !== 'int') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
    }
}
