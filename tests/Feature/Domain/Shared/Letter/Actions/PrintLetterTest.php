<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Letter\Actions\PrintLetter;
use App\Domain\Shared\Letter\Enums\LetterMedia;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Letter\Support\CarbonCopy;
use App\Domain\Shared\Letter\Support\PdfFile;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\Fixtures\Support\BrokenMappingLetter;
use Tests\Fixtures\Support\ExtraNoticeLetter;
use Tests\Fixtures\Support\MalayNoticeLetter;
use Tests\Fixtures\Support\NumberedPrintedLetter;
use Tests\Fixtures\Support\PrintedNoticeLetter;

beforeEach(function (): void {
    Storage::fake('media');
    config(['system.letters.types' => [
        'printed' => PrintedNoticeLetter::class,
        'malay' => MalayNoticeLetter::class,
        'numbered' => NumberedPrintedLetter::class,
        'plain' => ExtraNoticeLetter::class,
    ]]);
    $this->organization = Organization::factory()->create(['name' => 'Acme Sdn Bhd']);
    $this->printer = User::factory()->create();
    Profile::factory()->for($this->organization)->for($this->printer)->create();
});

function printable(string $type, array $attributes = []): Letter
{
    return Letter::factory()->ofType($type)->create([
        'source_type' => (new Organization)->getMorphClass(),
        'source_id' => test()->organization->id,
        'extra' => ['remark' => 'Reminder'],
        ...$attributes,
    ]);
}

function printLetter(Letter $letter, bool $preview = false): PdfFile
{
    return PrintLetter::make()->execute($letter, test()->printer, $preview);
}

test('a preview assigns no number and keeps nothing', function (): void {
    Pdf::fake();
    $letter = printable('numbered');

    $file = printLetter($letter, preview: true);

    expect($file)->toBeInstanceOf(PdfFile::class)
        ->and($letter->fresh()?->reference_number)->toBeNull()
        ->and($letter->fresh()?->date)->toBeNull()
        ->and($letter->getMedia(LetterMedia::Prints))->toBeEmpty();
    Pdf::assertSee('Draft');
});

test('a preview of a dated letter uses its date', function (): void {
    Pdf::fake();
    $letter = printable('malay', ['date' => '2026-03-10']);

    printLetter($letter, preview: true);

    Pdf::assertSee('10 Mac 2026');
});

test('a preview of an undated letter uses today', function (): void {
    Pdf::fake();
    $this->travelTo(CarbonImmutable::parse('2026-07-04 10:00', config()->string('system.timezone')));

    printLetter(printable('malay'), preview: true);

    Pdf::assertSee('4 Julai 2026');
});

test('a print assigns a number and does not mark a draft', function (): void {
    Pdf::fake();
    $letter = printable('numbered');

    printLetter($letter);

    expect($letter->fresh()?->reference_number)->toBe('N/1')
        ->and($letter->fresh()?->date)->not->toBeNull();
    Pdf::assertSee('N/1');
    Pdf::assertDontSee('Draft');
});

test('a print keeps the number of a reprint', function (): void {
    Pdf::fake();
    $letter = printable('numbered');

    printLetter($letter);
    printLetter($letter);

    expect($letter->fresh()?->reference_number)->toBe('N/1');
});

test('extra fields reach the view', function (): void {
    Pdf::fake();

    printLetter(printable('printed', ['extra' => ['remark' => 'Final reminder']]), preview: true);

    Pdf::assertSee('Final reminder');
});

test('the date prints in the format and locale of the type', function (string $type, string $month): void {
    Pdf::fake();

    printLetter(printable($type, ['date' => '2026-03-10']), preview: true);

    Pdf::assertSee("10 {$month} 2026");
})->with([
    'ms-MY' => ['malay', 'Mac'],
    'default locale' => ['printed', 'March'],
]);

test('the signer name and position come from the signature profile, also after the position is deleted', function (): void {
    Pdf::fake();
    $position = Position::factory()->for($this->organization)->create(['name' => 'Director']);
    $signer = User::factory()->create(['name' => 'Aminah Binti Ali']);
    Profile::factory()->for($this->organization)->for($signer)->create(['position_id' => $position->id]);
    $letter = printable('printed');
    $letter->act(Act::Sign, $signer);

    $position->delete();

    printLetter($letter->fresh(), preview: true);

    Pdf::assertSee(['Aminah Binti Ali', 'Director']);
});

test('the carbon copies print', function (): void {
    Pdf::fake();
    $letter = printable('printed');
    $letter->carbon_copies = [new CarbonCopy('Finance Unit')];
    $letter->save();

    printLetter($letter, preview: true);

    Pdf::assertSee('Finance Unit');
});

test('a letter with a letterhead prints its images', function (): void {
    Pdf::fake();
    Letterhead::factory()->withImages()->create(['organization_id' => $this->organization->id]);

    printLetter(printable('printed', ['with_letterhead' => true]), preview: true);

    Pdf::assertSee("url('data:image/png;base64,");
});

test('a letter without a letterhead prints with none when none is found', function (): void {
    Pdf::fake();

    printLetter(printable('printed', ['with_letterhead' => true]), preview: true);

    Pdf::assertDontSee('data:image');
});

test('a letter without the letterhead option prints with none', function (): void {
    Pdf::fake();
    Letterhead::factory()->withImages()->create(['organization_id' => $this->organization->id]);

    printLetter(printable('printed', ['with_letterhead' => false]), preview: true);

    Pdf::assertDontSee('data:image');
});

test('a deleted image in the letter data refuses the print', function (): void {
    Pdf::fake();
    $letter = printable('printed', ['extra' => ['remark' => 'x', 'stamp' => (string) str()->uuid()]]);

    expect(fn (): PdfFile => printLetter($letter))->toThrow(ValidationException::class);
});

test('a refused print assigns no number and no date and leaves the sequence', function (): void {
    Pdf::fake();
    $refused = printable('numbered', ['extra' => ['remark' => 'x', 'stamp' => (string) str()->uuid()]]);

    expect(fn (): PdfFile => printLetter($refused))->toThrow(ValidationException::class);

    expect($refused->fresh()?->reference_number)->toBeNull()
        ->and($refused->fresh()?->date)->toBeNull()
        ->and($refused->fresh()?->sequence_number)->toBeNull();

    printLetter(printable('numbered'));
    expect(Letter::query()->whereNotNull('reference_number')->value('reference_number'))->toBe('N/1');
});

test('a letter type mapping error is not reported as a deleted file', function (): void {
    Pdf::fake();
    config(['system.letters.types' => ['broken' => BrokenMappingLetter::class]]);

    expect(fn (): PdfFile => printLetter(printable('broken')))->toThrow(InvalidArgumentException::class);
});

test('a printer without a profile prints a letter with the letterhead option', function (): void {
    Pdf::fake();
    $printer = User::factory()->create();

    PrintLetter::make()->execute(printable('printed', ['with_letterhead' => true]), $printer, preview: true);

    Pdf::assertSee('Reminder');
});

test('a kept print stores the bytes with the printer and time', function (): void {
    Pdf::fake();
    $this->travelTo(CarbonImmutable::parse('2026-07-04 10:00:00'));
    $letter = printable('printed', ['reference_number' => 'REF/1']);

    $file = printLetter($letter);
    $media = $letter->fresh()->getMedia(LetterMedia::Prints);

    expect($media)->toHaveCount(1)
        ->and($media[0]->getCustomProperty('user_id'))->toBe($this->printer->id)
        ->and($media[0]->getCustomProperty('printed_at'))->toBe(now()->toIso8601String())
        ->and(Storage::disk('media')->get($media[0]->getPathRelativeToRoot()))->toBe($file->content);
});

test('a type without kept prints stores nothing', function (): void {
    Pdf::fake();
    $letter = printable('malay', ['reference_number' => 'REF/1']);

    printLetter($letter);

    expect($letter->fresh()->getMedia(LetterMedia::Prints))->toBeEmpty();
});

test('a preview of a type with kept prints stores nothing', function (): void {
    Pdf::fake();
    $letter = printable('printed');

    printLetter($letter, preview: true);

    expect($letter->fresh()->getMedia(LetterMedia::Prints))->toBeEmpty();
});

test('the file downloads and shows inline as a pdf', function (): void {
    Pdf::fake();
    $file = printLetter(printable('printed'), preview: true);

    $inline = $file->inline();
    $download = $file->download();

    expect($inline->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($inline->headers->get('Content-Disposition'))->toContain('inline')
        ->and($inline->getContent())->toBe($file->content)
        ->and($download->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($download->headers->get('Content-Disposition'))->toContain($file->name);
});

test('a real print keeps the downloaded bytes', function (): void {
    $letter = printable('printed', ['reference_number' => 'REF/1']);

    $file = printLetter($letter);
    $media = $letter->fresh()->getMedia(LetterMedia::Prints);

    expect($file->content)->toStartWith('%PDF')
        ->and($media)->toHaveCount(1)
        ->and(Storage::disk('media')->get($media[0]->getPathRelativeToRoot()))->toBe($file->content);
})->group('chrome');

test('a real print numbers the items', function (): void {
    expect(shell_exec('command -v pdftotext'))->not->toBeEmpty('pdftotext (poppler-utils) is required for this test.');

    $path = storage_path('framework/testing/'.str()->uuid7().'.pdf');
    file_put_contents($path, printLetter(printable('printed'), preview: true)->content);

    $text = (string) shell_exec('pdftotext '.escapeshellarg($path).' -');
    File::delete($path);

    expect(strtolower($text))->toContain('reminder')->and($text)->toContain('1.')->toContain('2.');
})->group('chrome');
