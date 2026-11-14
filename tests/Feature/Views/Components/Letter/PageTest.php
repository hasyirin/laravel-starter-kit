<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Actions\RenderPdf;
use App\Domain\User\Support\Preferences;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\View\ViewException;

/**
 * @param  view-string  $view
 * @param  array<string, mixed>  $viewData
 */
function renderLetterPage(string $view, array $viewData = []): string
{
    $path = storage_path('framework/testing/'.str()->uuid7().'.pdf');

    test()->letterPagePath = $path;

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

/**
 * @return Collection<int, array{name: string, type: string}>
 */
function letterPageFonts(string $path): Collection
{
    return str((string) shell_exec('pdffonts '.escapeshellarg($path)))
        ->explode("\n")
        ->skip(2)
        ->filter(fn (string $line): bool => trim($line) !== '')
        ->map(fn (string $line): array => [
            'name' => str($line)->before(' ')->after('+')->toString(),
            'type' => str($line)->match('/^\S+\s+(.+?)\s+\S+(?:\s+(?:yes|no)){3}\s+\d+\s+\d+\s*$/')->toString(),
        ])
        ->sortBy('name')
        ->values();
}

function letterPagePoints(float $millimeters): float
{
    return $millimeters * 72 / 25.4;
}

beforeEach(function (): void {
    $this->letterPagePath = null;
});

afterEach(function (): void {
    if ($this->letterPagePath !== null) {
        File::delete($this->letterPagePath);
    }
});

it('refuses an html first header with an html header', function (): void {
    expect(fn () => $this->blade('<x-letter.page><x-slot:firstHeader>First</x-slot:firstHeader><x-slot:header>Every</x-slot:header>Body</x-letter.page>'))
        ->toThrow(ViewException::class, 'A letter page cannot have an HTML first header and an HTML header.');
});

it('refuses an html first footer', function (): void {
    expect(fn () => $this->blade('<x-letter.page><x-slot:firstFooter>First</x-slot:firstFooter>Body</x-letter.page>'))
        ->toThrow(ViewException::class, 'A letter page cannot have an HTML first footer.');
});

it('refuses an image that is not a data uri', function (string $slot): void {
    expect(fn () => $this->blade("<x-letter.page><x-slot:{$slot} src=\"https://example.com/logo.png\"></x-slot:{$slot}>Body</x-letter.page>"))
        ->toThrow(ViewException::class, 'A letter page image must be a data: URI.');
})->with(['header', 'firstHeader', 'footer', 'firstFooter']);

it('prints a first page image header and footer on page 1 only', function (): void {
    $path = renderLetterPage('tests::letters.image-letterhead', ['image' => letterPageImage(), 'everyPage' => false]);

    expect(letterPageWords($path))->toHaveCount(3)
        ->and(letterPageImages($path)->pluck('page')->all())->toBe([1, 1]);

})->group('chrome');

it('prints an every page image header and footer on each page at full width', function (): void {
    $path = renderLetterPage('tests::letters.image-letterhead', ['image' => letterPageImage(), 'everyPage' => true]);

    expect(letterPageWords($path))->toHaveCount(3)
        ->and(letterPageImages($path)->pluck('page')->all())->toBe([1, 1, 2, 2, 3, 3])
        ->and(letterPageImages($path)->pluck('ppi')->all())->each->toBeBetween(299, 301);

})->group('chrome');

it('prints an html header and footer on each page', function (): void {
    $path = renderLetterPage('tests::letters.html-letterhead');

    $pages = letterPageWords($path);

    expect($pages)->toHaveCount(3);

    $pages->each(function (Collection $words): void {
        expect($words->pluck('text'))->toContain('Office', 'Footer');
    });

})->group('chrome');

it('keeps body text clear of an html header and footer', function (): void {
    $path = renderLetterPage('tests::letters.html-letterhead');

    $pages = letterPageWords($path);

    expect($pages)->toHaveCount(3);

    $pages->each(function (Collection $words): void {
        $body = $words->where('text', 'Line');

        expect($body)->not->toBeEmpty()
            ->and($body->min('top'))->toBeGreaterThanOrEqual(letterPagePoints(40) - 0.5)
            ->and($body->max('bottom'))->toBeLessThanOrEqual(letterPagePoints(297 - 25 - 20) + 0.5);
    });
})->group('chrome');

it('prints page numbers as page of pages', function (): void {
    $path = renderLetterPage('tests::letters.long');

    $pages = letterPageWords($path);

    expect($pages)->toHaveCount(3);

    $pages->each(function (Collection $words, int $index): void {
        expect($words->pluck('text')->implode(' '))->toContain(($index + 1).' / 3');
    });

})->group('chrome');

it('prints the text and the page number in the bundled fonts only', function (): void {
    $fonts = letterPageFonts(renderLetterPage('tests::letters.fonts'));

    expect($fonts->pluck('name')->all())->toBe([
        'Inter-Bold',
        'Inter-BoldItalic',
        'Inter-Italic',
        'Inter-Regular',
        'JetBrainsMono-Bold',
        'JetBrainsMono-Regular',
    ])->and($fonts->pluck('type')->unique()->all())->toBe(['CID TrueType']);
})->group('chrome');

it('prints the page number in the bundled font when the body has no regular text', function (): void {
    $fonts = letterPageFonts(renderLetterPage('tests::letters.bold'));

    expect($fonts->pluck('name')->all())->toBe(['Inter-Bold', 'Inter-Regular']);
})->group('chrome');
