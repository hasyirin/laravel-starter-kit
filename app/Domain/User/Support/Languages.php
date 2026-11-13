<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use Illuminate\Support\Collection;

final class Languages
{
    /**
     * @return Collection<string, string>
     */
    public static function all(): Collection
    {
        /** @var Collection<string, string> $languages */
        $languages = collect(config()->array('system.languages'))
            ->filter(fn (mixed $name, int|string $code): bool => is_string($name) && is_string($code));

        return $languages;
    }

    /**
     * @return Collection<string, string>
     */
    public static function abbreviations(): Collection
    {
        $abbreviations = config()->array('system.language_abbreviations');

        return self::all()->map(function (string $name, string $code) use ($abbreviations): string {
            $abbreviation = $abbreviations[$code] ?? null;

            return is_string($abbreviation) ? $abbreviation : str($code)->upper()->value();
        });
    }

    public static function default(): string
    {
        $languages = self::all();
        $default = config()->string('system.language');

        if ($languages->has($default)) {
            return $default;
        }

        return $languages->keys()->first() ?? $default;
    }

    /**
     * @param  array<mixed>  $candidates
     */
    public static function firstListed(array $candidates): ?string
    {
        $languages = self::all();

        $listed = collect($candidates)->first(fn (mixed $candidate): bool => is_string($candidate) && $languages->has($candidate));

        return is_string($listed) ? $listed : null;
    }
}
