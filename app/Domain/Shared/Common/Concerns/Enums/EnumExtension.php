<?php

namespace App\Domain\Shared\Common\Concerns\Enums;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

/**
 * @mixin  BackedEnum
 */
trait EnumExtension
{
    use EnumStringExtension;

    public function label(): Htmlable|string
    {
        return $this->headline();
    }

    /**
     * @return Collection<int, self>
     */
    public static function collect(): Collection
    {
        /** @var Collection<int, self> $cases */
        $cases = Collection::make(self::cases());

        return $cases;
    }

    /**
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     *
     * @param  callable(self, int): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
     * @return Collection<TMapWithKeysKey, TMapWithKeysValue>
     */
    public static function mapWithKeys(callable $callback): Collection
    {
        return static::collect()->mapWithKeys($callback);
    }

    /**
     * @return Collection<array-key, Htmlable|string>
     */
    public static function options(): Collection
    {
        return static::mapWithKeys(fn (self $case): array => [$case->value => $case->label()]);
    }

    /**
     * @template TMapValue
     *
     * @param  callable(self, int): TMapValue  $callback
     * @return Collection<int, TMapValue>
     */
    public static function map(callable $callback): Collection
    {
        return static::collect()->map($callback);
    }

    /**
     * @return Collection<int, int|string>
     */
    public static function values(): Collection
    {
        /** @var Collection<int, int|string> $values */
        $values = static::map(fn (self $type) => $type->value);

        return $values;
    }

    /**
     * @param  Arrayable<array-key, int|string|self>|array<array-key, int|string|self>|int|string|self  $status
     */
    public function is(Arrayable|array|int|string|self $status): bool
    {
        return Collection::make(is_array($status) || $status instanceof Arrayable ? $status : [$status])
            ->map(fn (int|string|self $status) => $status instanceof self ? $status->value : $status)
            ->containsStrict($this->value);
    }

    /**
     * @param  Arrayable<array-key, int|string|self>|array<array-key, int|string|self>|int|string|self  $status
     */
    public function not(Arrayable|array|int|string|self $status): bool
    {
        return ! $this->is($status);
    }

    public function ray(): self
    {
        ray($this);

        return $this;
    }

    public function dd(): void
    {
        if (app()->isProduction()) {
            return;
        }

        dd($this);
    }
}
