<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Support;

use Illuminate\Support\Collection;
use SensitiveParameter;

class MaskedBody
{
    public static function of(#[SensitiveParameter] ?string $body): ?string
    {
        if ($body === null || $body === '') {
            return null;
        }

        return mb_strcut(self::mask($body), 0, config()->integer('system.api_log.body_limit'));
    }

    private static function mask(#[SensitiveParameter] string $body): string
    {
        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            return $body;
        }

        return (string) json_encode(self::maskValues($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private static function maskValues(#[SensitiveParameter] array $values): array
    {
        return collect($values)
            ->map(function (mixed $value, int|string $key): mixed {
                if (is_string($key) && self::masked($key)) {
                    return '[hidden]';
                }

                if (is_array($value)) {
                    return self::maskValues($value);
                }

                return $value;
            })
            ->all();
    }

    private static function masked(string $key): bool
    {
        $normalized = self::normalize($key);

        return self::maskedKeys()->contains(fn (string $masked): bool => str_contains($normalized, $masked));
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
