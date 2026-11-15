<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Support;

use Illuminate\Support\Collection;
use JsonException;
use SensitiveParameter;
use stdClass;

class MaskedBody
{
    public static function of(#[SensitiveParameter] ?string $body): ?string
    {
        if ($body === null || $body === '') {
            return null;
        }

        if (strlen($body) > config()->integer('system.api_log.masked_body_limit')) {
            return null;
        }

        $masked = self::mask($body);

        if ($masked === null) {
            return null;
        }

        return mb_strcut($masked, 0, config()->integer('system.api_log.body_limit'));
    }

    private static function mask(#[SensitiveParameter] string $body): ?string
    {
        try {
            $decoded = json_decode($body, false, flags: JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            if (str($body)->trim()->startsWith(['{', '['])) {
                return null;
            }

            return $body;
        }

        if (! is_array($decoded) && ! $decoded instanceof stdClass) {
            return $body;
        }

        try {
            return json_encode(self::maskValue($decoded, self::maskedKeys()), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * @param  Collection<int, string>  $maskedKeys
     */
    private static function maskValue(#[SensitiveParameter] mixed $value, Collection $maskedKeys): mixed
    {
        if ($value instanceof stdClass) {
            return (object) self::maskValues(get_object_vars($value), $maskedKeys);
        }

        if (is_array($value)) {
            return self::maskValues($value, $maskedKeys);
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @param  Collection<int, string>  $maskedKeys
     * @return array<array-key, mixed>
     */
    private static function maskValues(#[SensitiveParameter] array $values, Collection $maskedKeys): array
    {
        return collect($values)
            ->map(function (mixed $value, int|string $key) use ($maskedKeys): mixed {
                if (is_string($key) && self::masked($key, $maskedKeys)) {
                    return '[hidden]';
                }

                return self::maskValue($value, $maskedKeys);
            })
            ->all();
    }

    /**
     * @param  Collection<int, string>  $maskedKeys
     */
    private static function masked(string $key, Collection $maskedKeys): bool
    {
        $normalized = self::normalize($key);

        return $maskedKeys->contains(fn (string $masked): bool => str_contains($normalized, $masked));
    }

    /**
     * @return Collection<int, string>
     */
    private static function maskedKeys(): Collection
    {
        /** @var list<string> $keys */
        $keys = config()->array('system.api_log.masked_keys');

        return collect($keys)->map(self::normalize(...));
    }

    private static function normalize(string $key): string
    {
        return str($key)->lower()->remove(['_', '-'])->value();
    }
}
