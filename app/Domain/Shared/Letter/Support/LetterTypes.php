<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LogicException;

final class LetterTypes
{
    /**
     * @return array<string, LetterType>
     */
    public static function all(): array
    {
        return collect(self::registered())
            ->map(function (string $class, string $key): LetterType {
                $type = resolve($class);

                throw_unless($type instanceof LetterType, LogicException::class, "The container did not build a LetterType for [{$key}].");

                return $type;
            })
            ->all();
    }

    public static function find(string $key): LetterType
    {
        return self::tryFind($key) ?? throw new InvalidArgumentException("Unknown letter type [{$key}].");
    }

    public static function tryFind(string $key): ?LetterType
    {
        return self::all()[$key] ?? null;
    }

    public static function keyOf(LetterType|string $type): string
    {
        $class = is_string($type) ? $type : $type::class;
        $key = array_search($class, self::registered(), true);

        throw_if($key === false, InvalidArgumentException::class, "Unregistered letter type [{$class}].");

        return (string) $key;
    }

    /**
     * @return array<string, LetterType>
     */
    public static function forSource(Model $source): array
    {
        return collect(self::all())
            ->filter(fn (LetterType $type): bool => $source instanceof ($type->source()))
            ->all();
    }

    /**
     * @return array<string, class-string<LetterType>>
     */
    private static function registered(): array
    {
        return collect(config()->array('system.letters.types'))
            ->mapWithKeys(function (mixed $class, int|string $key): array {
                throw_if(! is_string($key) || ! is_string($class) || ! is_subclass_of($class, LetterType::class), InvalidArgumentException::class, 'system.letters.types must map a key to a LetterType class.');

                return [$key => $class];
            })
            ->all();
    }
}
