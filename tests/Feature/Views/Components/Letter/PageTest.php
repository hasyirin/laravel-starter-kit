<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Actions\RenderPdf;
use App\Domain\User\Support\Preferences;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * @param  view-string  $view
 * @param  array<string, mixed>  $viewData
 */
function renderLetterPage(string $view, array $viewData = []): string
{
    $path = storage_path('framework/testing/'.str()->uuid7().'.pdf');

    RenderPdf::make()->execute($view, $viewData, Preferences::current())->save($path);

    return $path;
}

function letterPageImage(): string
{
    $image = imagecreatetruecolor(2480, 400);
    imagefill($image, 0, 0, (int) imagecolorallocate($image, 200, 30, 30));

    ob_start();
    imagepng($image);

    return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
}

/**
 * @return Collection<int, Collection<int, array{text: string, top: float, bottom: float}>>
 */
function letterPageWords(string $path): Collection
{
    $document = HTMLDocument::createFromString((string) shell_exec('pdftotext -bbox '.escapeshellarg($path).' -'), LIBXML_NOERROR);

    return collect($document->querySelectorAll('page'))->values()->map(
        fn (Element $page): Collection => collect($page->querySelectorAll('word'))->values()->map(fn (Element $word): array => [
            'text' => (string) $word->textContent,
            'top' => (float) $word->getAttribute('ymin'),
            'bottom' => (float) $word->getAttribute('ymax'),
        ]),
    );
}

/**
 * @return Collection<int, array{page: int, ppi: int}>
 */
function letterPageImages(string $path): Collection
{
    return str((string) shell_exec('pdfimages -list '.escapeshellarg($path)))
        ->explode("\n")
        ->skip(2)
        ->filter(fn (string $line): bool => trim($line) !== '')
        ->map(fn (string $line): array => preg_split('/\s+/', trim($line)) ?: [])
        ->map(fn (array $columns): array => ['page' => (int) $columns[0], 'ppi' => (int) $columns[12]])
        ->values();
}

it('prints a first page image header and footer on page 1 only', function (): void {
    $path = renderLetterPage('tests::letters.image-letterhead', ['image' => letterPageImage(), 'everyPage' => false]);

    expect(letterPageWords($path))->toHaveCount(3)
        ->and(letterPageImages($path)->pluck('page')->all())->toBe([1, 1]);

    File::delete($path);
})->group('chrome');

it('prints an every page image header and footer on each page at full width', function (): void {
    $path = renderLetterPage('tests::letters.image-letterhead', ['image' => letterPageImage(), 'everyPage' => true]);

    expect(letterPageWords($path))->toHaveCount(3)
        ->and(letterPageImages($path)->pluck('page')->all())->toBe([1, 1, 2, 2, 3, 3])
        ->and(letterPageImages($path)->pluck('ppi')->all())->each->toBeBetween(299, 301);

    File::delete($path);
})->group('chrome');

it('prints an html header and footer on each page', function (): void {
    $path = renderLetterPage('tests::letters.html-letterhead');

    $pages = letterPageWords($path);

    expect($pages)->toHaveCount(3);

    $pages->each(function (Collection $words): void {
        expect($words->pluck('text'))->toContain('Office', 'Footer');
    });

    File::delete($path);
})->group('chrome');

it('keeps body text clear of an html header and footer', function (): void {
    $path = renderLetterPage('tests::letters.html-letterhead');

    letterPageWords($path)->each(function (Collection $words): void {
        $header = $words->where('text', 'Office');
        $footer = $words->where('text', 'Footer');
        $body = $words->where('text', 'Line');

        expect($header)->not->toBeEmpty()
            ->and($footer)->not->toBeEmpty()
            ->and($body)->not->toBeEmpty()
            ->and($body->min('top'))->toBeGreaterThan($header->max('bottom'))
            ->and($body->max('bottom'))->toBeLessThan($footer->min('top'));
    });

    File::delete($path);
})->group('chrome');

it('prints page numbers as page of pages', function (): void {
    $path = renderLetterPage('tests::letters.long');

    $pages = letterPageWords($path);

    expect($pages)->toHaveCount(3);

    $pages->each(function (Collection $words, int $index): void {
        expect($words->pluck('text')->implode(' '))->toContain(($index + 1).' / 3');
    });

    File::delete($path);
})->group('chrome');
