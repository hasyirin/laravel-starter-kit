<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Actions\RenderPdf;
use App\Domain\User\Support\Preferences;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

it('renders a view to a pdf', function (): void {
    Pdf::fake();

    RenderPdf::make()->execute('tests::letters.plain', ['title' => 'Hello'], Preferences::current())->save('hello.pdf');

    Pdf::assertSaved(fn (PdfBuilder $pdf): bool => str_contains($pdf->html, 'Hello'));
});

it('marks a draft', function (): void {
    Pdf::fake();

    RenderPdf::make()->execute('tests::letters.plain', ['title' => 'Hello'], Preferences::current(), draft: true)->save('draft.pdf');
    RenderPdf::make()->execute('tests::letters.plain', ['title' => 'Hello'], Preferences::current())->save('final.pdf');

    Pdf::assertSaved(fn (PdfBuilder $pdf, string $path): bool => $path === 'draft.pdf' && str_contains($pdf->html, __('letters/letters.draft')));
    Pdf::assertSaved(fn (PdfBuilder $pdf, string $path): bool => $path === 'final.pdf' && ! str_contains($pdf->html, __('letters/letters.draft')));
});

it('formats dates in the letter locale and time zone', function (): void {
    Pdf::fake();
    $date = CarbonImmutable::parse('2026-03-10');

    RenderPdf::make()->execute('tests::letters.plain', ['title' => 'Hello', 'date' => $date], new Preferences('Asia/Kuala_Lumpur', 'ms-MY'))->save('ms.pdf');
    RenderPdf::make()->execute('tests::letters.plain', ['title' => 'Hello', 'date' => $date], new Preferences('Asia/Kuala_Lumpur', 'en-MY'))->save('en.pdf');

    Pdf::assertSaved(fn (PdfBuilder $pdf, string $path): bool => $path === 'ms.pdf' && str_contains($pdf->html, 'Mac'));
    Pdf::assertSaved(fn (PdfBuilder $pdf, string $path): bool => $path === 'en.pdf' && str_contains($pdf->html, 'Mar'));
});

it('prints html in letter data as text', function (): void {
    Pdf::fake();

    RenderPdf::make()->execute('tests::letters.plain', ['title' => '<b>bold</b>'], Preferences::current())->save('escaped.pdf');

    Pdf::assertSaved(fn (PdfBuilder $pdf): bool => str_contains($pdf->html, '&lt;b&gt;') && ! str_contains($pdf->html, '<b>bold</b>'));
});

it('loads no local file', function (): void {
    $path = storage_path('framework/testing/'.str()->uuid7().'.pdf');

    RenderPdf::make()->execute('tests::letters.file-image', ['imagePath' => base_path('tests/Fixtures/Files/pixel.png')], Preferences::current())->save($path);

    expect(shell_exec('pdftotext '.escapeshellarg($path).' -'))->toContain('No local image')
        ->and(shell_exec('pdfimages -list '.escapeshellarg($path)))->not->toMatch('/^\s*1\s/m');

    File::delete($path);
})->group('chrome');
