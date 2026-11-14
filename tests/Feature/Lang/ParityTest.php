<?php

declare(strict_types=1);

use App\Domain\User\Support\Languages;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @return Collection<string, string>
 */
function translations(string $language): Collection
{
    /** @var Collection<string, string> $lines */
    $lines = collect(File::allFiles(lang_path($language)))
        ->mapWithKeys(fn (SplFileInfo $file): array => [
            str($file->getRelativePathname())->beforeLast('.php')->value() => require $file->getPathname(),
        ])
        ->pipe(fn (Collection $files): Collection => collect(Arr::dot($files->all())));

    return $lines;
}

/**
 * @return Collection<int, string>
 */
function placeholders(string $line): Collection
{
    return str($line)->matchAll('/:[a-z_]+/')->unique()->sort()->values();
}

/**
 * @return Collection<int, string>
 */
function otherLanguages(): Collection
{
    return Languages::all()->keys()->reject('en')->values();
}

test('each language has the same translation keys as English', function (): void {
    $english = translations('en')->keys();

    otherLanguages()->each(function (string $language) use ($english): void {
        $other = translations($language)->keys();

        expect($english->diff($other)->values()->all())->toBe([], "Keys that lang/{$language} does not have")
            ->and($other->diff($english)->values()->all())->toBe([], "Keys that only lang/{$language} has");
    });
});

test('each translation keeps the placeholders of the English text', function (): void {
    $english = translations('en');

    otherLanguages()->each(function (string $language) use ($english): void {
        $other = translations($language);

        $different = $english
            ->filter(fn (string $line, string $key): bool => $other->has($key) && placeholders($line)->all() !== placeholders($other[$key])->all())
            ->keys();

        expect($different->all())->toBe([], "Lines of lang/{$language} with other placeholders");
    });
});

test('no translation is empty', function (): void {
    otherLanguages()->each(function (string $language): void {
        $empty = translations($language)->filter(fn (string $line): bool => trim($line) === '')->keys();

        expect($empty->all())->toBe([], "Empty lines of lang/{$language}");
    });
});

test('no translation has a word or a mark that the style forbids', function (): void {
    otherLanguages()->each(function (string $language): void {
        /** @var array<string, string> $sentences */
        $sentences = File::json(lang_path("{$language}.json"));

        $forbidden = translations($language)
            ->merge($sentences)
            ->filter(fn (string $line): bool => preg_match('/\blalai\b|tembok api|—|&mdash;/iu', $line) === 1)
            ->keys();

        expect($forbidden->all())->toBe([], "Lines of lang/{$language} with a forbidden word or an em dash");
    });
});

test('a sentence that a package uses as a key keeps its placeholders', function (): void {
    otherLanguages()->each(function (string $language): void {
        /** @var array<string, string> $sentences */
        $sentences = File::json(lang_path("{$language}.json"));

        $different = collect($sentences)
            ->filter(fn (string $line, string $key): bool => trim($line) === '' || placeholders($key)->all() !== placeholders($line)->all())
            ->keys();

        expect($different->all())->toBe([], "Sentences of lang/{$language}.json that are empty or have other placeholders");
    });
});
