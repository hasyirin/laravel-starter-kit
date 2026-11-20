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
            return self::isInteger($value);
        }

        return match (self::uniqueIdTrait($model)) {
            HasUuids::class => is_string($value) && str($value)->isUuid(),
            HasUlids::class => is_string($value) && str($value)->isUlid(),
            default => true,
        };
    }

    /**
     * @phpstan-assert-if-true int|numeric-string $value
     */
    public static function isInteger(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
    }

    public static function absentKey(Model $model): int|string
    {
        if ($model->getKeyType() === 'int') {
            return 0;
        }

        return match (self::uniqueIdTrait($model)) {
            HasUuids::class => '00000000-0000-0000-0000-000000000000',
            HasUlids::class => str_repeat('0', 26),
            default => '0',
        };
    }

    private static function uniqueIdTrait(Model $model): ?string
    {
        if (! in_array($model->getKeyName(), $model->uniqueIds(), true)) {
            return null;
        }

        $traits = class_uses_recursive($model);

        return collect([HasUuids::class, HasUlids::class])->first(fn (string $trait): bool => in_array($trait, $traits, true));
    }
}
