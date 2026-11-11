<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Models\AuditEntry;
use App\Domain\Shared\Letter\Actions\PrintManualLetter;
use App\Domain\Shared\Letter\Actions\SaveLetter;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\CarbonCopy;
use App\Domain\Shared\Letter\Support\LetterTypes;
use App\Domain\Shared\Letter\Support\PdfFile;
use App\Domain\Shared\Letter\Support\PrintDetails;
use App\Domain\Shared\Media\Enums\MediaCollection;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\Components\FileUpload;
use Carbon\CarbonImmutable;
use Hasyirin\RunningNumber\Models\RunningNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Fixtures\Models\Crest;
use Tests\Fixtures\Support\CrestLetter;
use Tests\Fixtures\Support\NoticeLetter;
use Tests\Fixtures\Support\NumberedPrintedLetter;
use Tests\Fixtures\Support\PrintedNoticeLetter;

beforeEach(function (): void {
    Storage::fake('media');
    Pdf::fake();
    config(['system.letters.types' => [
        'notice' => NoticeLetter::class,
        'printed' => PrintedNoticeLetter::class,
        'numbered' => NumberedPrintedLetter::class,
        'crest' => CrestLetter::class,
    ]]);
    $this->organization = Organization::factory()->create(['name' => 'Acme Sdn Bhd']);
    $this->admin = User::factory()->create();
    Profile::factory()->for($this->organization)->for($this->admin)->create();
});

function manualDetails(?User $signer = null, array $copies = [], ?string $reference = 'REF-9'): PrintDetails
{
    return new PrintDetails($reference, null, CarbonImmutable::parse('2026-03-10'), $signer, $copies, false, false);
}

function noticeData(array $overrides = []): array
{
    return ['organizationName' => 'Acme', 'works' => null, 'issuedOn' => '2026-03-01', ...$overrides];
}

function printManual(string $type, array $data, array $extra = [], ?PrintDetails $details = null, bool $preview = false, ?Organization $source = null, ?Letter $letter = null): PdfFile
{
    return PrintManualLetter::make()->execute(
        type: LetterTypes::find($type),
        data: $data,
        extra: $extra,
        details: $details ?? manualDetails(),
        admin: test()->admin,
        source: $source,
        preview: $preview,
        letter: $letter,
    );
}

function manualEntries(): Builder
{
    return AuditEntry::query()->where('event', 'printed-manual-letter');
}

function stampUpload(): TemporaryUploadedFile
{
    $item = Livewire::test(FileUpload::class, ['fileRules' => ['image']])->set('uploads', UploadedFile::fake()->image('stamp.png', 100, 100))->get('items')[0];

    return TemporaryUploadedFile::unserializeFromLivewireRequest($item['upload']);
}

test('a print writes one audit entry with the values', function (): void {
    $signer = User::factory()->create(['name' => 'Siti']);
    Profile::factory()->for($this->organization)->for($signer)->for(Position::factory()->for($this->organization)->create(['name' => 'Director']))->create();

    $file = printManual('printed', noticeData(), ['remark' => 'Reminder'], manualDetails($signer, [new CarbonCopy('Finance')]), source: $this->organization);

    $entry = manualEntries()->sole();
    expect($file->name)->toBe('ref-9.pdf')
        ->and($entry->event)->toBe('printed-manual-letter')
        ->and($entry->causer?->is($this->admin))->toBeTrue()
        ->and(data_get($entry->properties, 'type'))->toBe('printed')
        ->and(data_get($entry->properties, 'data.organizationName'))->toBe('Acme')
        ->and(data_get($entry->properties, 'extra.remark'))->toBe('Reminder')
        ->and(data_get($entry->properties, 'details.referenceNumber'))->toBe('REF-9')
        ->and(data_get($entry->properties, 'details.signer.name'))->toBe('Siti')
        ->and(data_get($entry->properties, 'source_type'))->toBe($this->organization->getMorphClass())
        ->and(data_get($entry->properties, 'source_id'))->toBe($this->organization->id);
    Pdf::assertSee('Director');
    Pdf::assertSee('Finance');
    Pdf::assertDontSee('Draft');
});

test('a print makes no letter row and does not move the sequence', function (): void {
    printManual('numbered', noticeData(), ['remark' => 'Reminder'], manualDetails(reference: null));

    expect(Letter::query()->withTrashed()->count())->toBe(0)
        ->and(RunningNumber::query()->count())->toBe(0);
});

test('a preview writes no audit entry and marks the page as a draft', function (): void {
    printManual('printed', noticeData(), ['remark' => 'Reminder'], preview: true);

    expect(manualEntries()->count())->toBe(0);
    Pdf::assertSee('Draft');
});

test('a ticked group prints its section', function (): void {
    printManual('notice', noticeData(['works' => ['contractor' => 'Builder Bhd']]));
    Pdf::assertSee('Builder Bhd');
});

test('an unticked group prints no section', function (): void {
    printManual('notice', noticeData());
    Pdf::assertDontSee('Builder Bhd');
});

test('an upload ends on the audit entry and its uuid is logged', function (): void {
    printManual('printed', noticeData(), ['remark' => 'Reminder', 'stamp' => stampUpload()]);

    $entry = manualEntries()->sole();
    $media = $entry->getMedia(MediaCollection::LetterImages)->sole();
    expect(data_get($entry->properties, 'extra.stamp'))->toBe($media->uuid)
        ->and($media->file_name)->toBe('stamp.png');
});

test('a preview keeps the upload out of the audit log', function (): void {
    printManual('printed', noticeData(), ['remark' => 'Reminder', 'stamp' => stampUpload()], preview: true);

    expect(manualEntries()->count())->toBe(0)->and(DB::table('media')->count())->toBe(0);
});

test('a failed audit entry leaves no stored image', function (): void {
    AuditEntry::updating(fn (): never => throw new RuntimeException('The audit entry failed.'));

    expect(fn (): PdfFile => printManual('printed', noticeData(), ['remark' => 'Reminder', 'stamp' => stampUpload()]))->toThrow(RuntimeException::class, 'The audit entry failed.');
    expect(manualEntries()->count())->toBe(0)
        ->and(Storage::disk('media')->allFiles())->toBeEmpty();
});

function manualCrestImage(): Media
{
    Schema::create('crests', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });
    Relation::morphMap(['crest' => Crest::class]);
    $crest = Crest::query()->create(['name' => 'Crest']);

    return $crest->addMedia(base_path('tests/Fixtures/Files/pixel.png'))->preservingOriginal()->toMediaCollection();
}

test('an image of the source stays on the source and its uuid is logged', function (): void {
    $media = manualCrestImage();
    $crest = Crest::query()->sole();

    PrintManualLetter::make()->execute(LetterTypes::find('crest'), ['logo' => $media->uuid], [], manualDetails(), $this->admin, $crest);

    $entry = manualEntries()->sole();
    expect($entry->getMedia(MediaCollection::LetterImages))->toBeEmpty()
        ->and(data_get($entry->properties, 'data.logo'))->toBe($media->uuid)
        ->and($crest->fresh()?->getMedia())->toHaveCount(1);
});

test('an image uuid of another owner is refused', function (): void {
    $media = manualCrestImage();

    expect(fn (): PdfFile => printManual('printed', noticeData(), ['remark' => 'x', 'stamp' => $media->uuid]))->toThrow(ValidationException::class);
    expect(manualEntries()->count())->toBe(0);
});

test('a missing required value is refused with a key for the part', function (): void {
    try {
        printManual('printed', noticeData(['organizationName' => '']), ['remark' => 'x']);
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe(['data.organizationName']);

        return;
    }

    throw new RuntimeException('The print was not refused.');
});

test('a signer without a position is refused', function (): void {
    $signer = User::factory()->create();
    Profile::factory()->for($this->organization)->for($signer)->create(['position_id' => null]);

    expect(fn (): PdfFile => printManual('printed', noticeData(), ['remark' => 'x'], manualDetails($signer)))->toThrow(ValidationException::class);
    expect(manualEntries()->count())->toBe(0);
});

test('a letter start logs the letter id', function (): void {
    $letter = Letter::factory()->ofType('printed')->create(['source_type' => $this->organization->getMorphClass(), 'source_id' => $this->organization->id]);

    printManual('printed', noticeData(), ['remark' => 'x'], source: $this->organization, letter: $letter);

    expect(data_get(manualEntries()->sole()->properties, 'letter_id'))->toBe($letter->id);
});

test('a letter of another source or type is refused', function (): void {
    $other = Organization::factory()->create();
    $foreign = Letter::factory()->ofType('printed')->create(['source_type' => $other->getMorphClass(), 'source_id' => $other->id]);
    $wrongType = Letter::factory()->ofType('notice')->create(['source_type' => $this->organization->getMorphClass(), 'source_id' => $this->organization->id]);

    expect(fn (): PdfFile => printManual('printed', noticeData(), ['remark' => 'x'], source: $this->organization, letter: $foreign))->toThrow(LogicException::class)
        ->and(fn (): PdfFile => printManual('printed', noticeData(), ['remark' => 'x'], source: $this->organization, letter: $wrongType))->toThrow(LogicException::class)
        ->and(fn (): PdfFile => printManual('printed', noticeData(), ['remark' => 'x'], letter: $foreign))->toThrow(LogicException::class);
});

function letterWithStamp(Organization $source): Letter
{
    $letter = Letter::factory()->ofType('printed')->create(['source_type' => $source->getMorphClass(), 'source_id' => $source->id]);
    $media = $letter->addMedia(base_path('tests/Fixtures/Files/pixel.png'))->preservingOriginal()->toMediaCollection(MediaCollection::LetterImages->value);
    $letter->update(['extra' => ['remark' => 'x', 'stamp' => $media->uuid]]);

    return $letter;
}

test('an image of the letter is copied onto the audit entry and stays when the letter image is replaced', function (): void {
    $letter = letterWithStamp($this->organization);

    printManual('printed', noticeData(), $letter->extra, source: $this->organization, letter: $letter);
    SaveLetter::make()->execute($letter, LetterTypes::find('printed'), $this->organization, $this->admin, manualDetails(), ['remark' => 'x', 'stamp' => stampUpload()]);

    $entry = manualEntries()->sole();
    $copy = $entry->getMedia(MediaCollection::LetterImages)->sole();
    expect(data_get($entry->properties, 'extra.stamp'))->toBe($copy->uuid)
        ->and(Storage::disk('media')->exists($copy->getPathRelativeToRoot()))->toBeTrue();
});

test('a failed audit entry leaves no copy of a letter image', function (): void {
    $letter = letterWithStamp($this->organization);
    $files = Storage::disk('media')->allFiles();
    AuditEntry::updating(fn (): never => throw new RuntimeException('The audit entry failed.'));

    expect(fn (): PdfFile => printManual('printed', noticeData(), $letter->extra, source: $this->organization, letter: $letter))->toThrow(RuntimeException::class, 'The audit entry failed.');
    expect(Storage::disk('media')->allFiles())->toBe($files);
});
