<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class LetterTypes
{
    /**
     * @return array<string, LetterType>
     */
    public static function all(): array
    {
        $types = [];

        foreach (self::registered() as $key => $class) {
            $types[$key] = new $class;
        }

        return $types;
    }

    public static function find(string $key): LetterType
    {
        $types = self::all();

        throw_unless(isset($types[$key]), InvalidArgumentException::class, "Unknown letter type [{$key}].");

        return $types[$key];
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
        $registered = [];

        foreach (config()->array('system.letters.types') as $key => $class) {
            throw_if(! is_string($key) || ! is_string($class) || ! is_subclass_of($class, LetterType::class), InvalidArgumentException::class, 'system.letters.types must map a key to a LetterType class.');

            $registered[$key] = $class;
        }

        return $registered;
    }
}
