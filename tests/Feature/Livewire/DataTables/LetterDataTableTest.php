<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Letter\Enums\LetterMedia;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\Components\FileUpload;
use App\Livewire\Components\LetterModal;
use App\Livewire\DataTables\LetterDataTable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\Fixtures\Models\Permit;
use Tests\Fixtures\Policies\PermitPolicy;
use Tests\Fixtures\Policies\ToggledLetterPolicy;
use Tests\Fixtures\Support\PermitLetter;
use Tests\Fixtures\Support\PermitPrintLetter;
use Tests\Fixtures\Support\PermitSignedLetter;

beforeEach(function (): void {
    Storage::fake('media');
    Schema::create('permits', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->boolean('hidden')->default(false);
        $table->boolean('locked')->default(false);
        $table->timestampsTz();
        $table->softDeletesTz();
    });
    Relation::morphMap(['permit' => Permit::class]);
    Gate::policy(Permit::class, PermitPolicy::class);
    config(['system.letters.types' => ['permit' => PermitLetter::class, 'signed' => PermitSignedLetter::class, 'printable' => PermitPrintLetter::class]]);

    $this->organization = Organization::factory()->create();
    $this->actor = User::factory()->create();
    Profile::factory()->for($this->organization)->for($this->actor)->create();
    $this->actingAs($this->actor);
    $this->permit = Permit::query()->create(['name' => 'Open']);
});

function tableLetter(string $type = 'permit', ?Permit $source = null, array $attributes = []): Letter
{
    return ($source ?? test()->permit)->letters()->create(['type' => $type, 'date' => '2026-03-04', ...$attributes]);
}

function letterTable(?Permit $source = null): Testable
{
    return Livewire::withoutLazyLoading()->test(LetterDataTable::class, ['source' => $source ?? test()->permit]);
}

test('the table lists the letters of its source only', function (): void {
    $mine = tableLetter();
    $other = tableLetter(source: Permit::query()->create(['name' => 'Other']));

    letterTable()->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$other]);
});

test('the table shows the type, reference number, signer, and signed scan of each letter', function (): void {
    $signer = User::factory()->create(['name' => 'Siti']);
    Profile::factory()->for($this->organization)->for($signer)->for(Position::factory()->for($this->organization)->create(['name' => 'Director']))->create();
    $signed = tableLetter('signed', attributes: ['reference_number' => 'REF-1']);
    $signed->act(Act::Sign, $signer);
    $signed->addMedia(UploadedFile::fake()->create('scan.pdf', 10, 'application/pdf'))->toMediaCollection(LetterMedia::SignedScan->value);
    $plain = tableLetter();

    letterTable()
        ->assertTableColumnStateSet('type', 'signed', $signed)
        ->assertTableColumnStateSet('reference_number', 'REF-1', $signed)
        ->assertTableColumnStateSet('signed_scan', true, $signed)
        ->assertTableColumnStateSet('signed_scan', false, $plain)
        ->assertSeeText(['Siti', 'Director']);
});

test('ten letters take the same number of queries as one', function (): void {
    $signer = User::factory()->create();
    Profile::factory()->for($this->organization)->for($signer)->for(Position::factory()->for($this->organization)->create())->create();
    $count = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        letterTable();

        return count(DB::getQueryLog());
    };
    $make = function () use ($signer): void {
        tableLetter('signed')->act(Act::Sign, $signer);
    };

    $make();
    letterTable();
    $one = $count();
    foreach (range(1, 9) as $ignored) {
        $make();
    }

    expect($count())->toBe($one);
});

test('the table refuses a user who cannot view the source', function (): void {
    $this->permit->update(['hidden' => true]);

    letterTable()->assertForbidden();
});

test('the create action follows the letter policy', function (): void {
    letterTable()->assertActionVisible(TestAction::make('create')->table());

    $this->permit->update(['locked' => true]);

    letterTable()->assertActionHidden(TestAction::make('create')->table());
});

test('the create action opens the letter modal', function (): void {
    letterTable()->callAction(TestAction::make('create')->table())->assertDispatchedTo(LetterModal::class, 'show');
});

test('the edit action opens the letter modal for the letter', function (): void {
    $letter = tableLetter();

    letterTable()->callAction(TestAction::make('edit')->table($letter))->assertDispatchedTo(LetterModal::class, 'show', id: $letter->id);
});

test('the table refreshes when the letter modal saves', function (): void {
    $component = letterTable();
    $letter = tableLetter();

    $component->dispatch(LetterModal::event())->assertCanSeeTableRecords([$letter]);
});

test('the row actions follow the letter policy', function (): void {
    $letter = tableLetter();
    Gate::policy(Letter::class, ToggledLetterPolicy::class);
    ToggledLetterPolicy::$update = false;
    ToggledLetterPolicy::$print = false;

    letterTable()
        ->assertActionHidden(TestAction::make('edit')->table($letter))
        ->assertActionHidden(TestAction::make('print')->table($letter))
        ->assertActionHidden(TestAction::make('preview')->table($letter))
        ->assertActionHidden(TestAction::make('upload_scan')->table($letter));

    ToggledLetterPolicy::$update = true;
    ToggledLetterPolicy::$print = true;

    letterTable()
        ->assertActionVisible(TestAction::make('edit')->table($letter))
        ->assertActionVisible(TestAction::make('print')->table($letter))
        ->assertActionVisible(TestAction::make('preview')->table($letter))
        ->assertActionVisible(TestAction::make('upload_scan')->table($letter));
});

test('the preview action links to the preview page in a new tab', function (): void {
    $letter = tableLetter();

    letterTable()->assertActionHasUrl(TestAction::make('preview')->table($letter), route('letters.preview', $letter))
        ->assertActionShouldOpenUrlInNewTab(TestAction::make('preview')->table($letter));
});

test('the print action downloads the letter pdf', function (): void {
    Pdf::fake();
    $letter = tableLetter('printable', attributes: ['reference_number' => 'REF-1']);

    letterTable()->callAction(TestAction::make('print')->table($letter))->assertFileDownloaded();
});

test('the print action says why a letter cannot be printed', function (): void {
    Pdf::fake();
    $letter = tableLetter('printable');

    letterTable()->callAction(TestAction::make('print')->table($letter))->assertNoFileDownloaded();
});

test('the delete action asks for confirmation and soft deletes the letter', function (): void {
    $letter = tableLetter();

    letterTable()->callAction(TestAction::make('delete')->table($letter))->assertHasNoErrors();

    expect(Letter::query()->find($letter->id))->toBeNull()
        ->and(Letter::withTrashed()->find($letter->id))->not->toBeNull();
});

test('a user who cannot delete cannot delete', function (): void {
    $this->permit->update(['locked' => true]);
    $letter = tableLetter();

    letterTable()->assertActionHidden(TestAction::make('delete')->table($letter));
    expect(Letter::query()->find($letter->id))->not->toBeNull();
});

test('the kept prints action lists each print with the printer and links to it', function (): void {
    $letter = tableLetter();
    $letter->addMediaFromString('%PDF')
        ->usingFileName('first.pdf')
        ->withCustomProperties(['user_id' => $this->actor->id, 'printed_at' => '2026-03-05T10:00:00+00:00'])
        ->toMediaCollection(LetterMedia::Prints->value);
    $media = $letter->fresh()->getFirstMedia(LetterMedia::Prints->value);

    $component = letterTable()->mountAction(TestAction::make('prints')->table($letter));
    $modal = $component->instance()->getMountedAction()?->getModalContent()?->toHtml();

    expect($modal)->toContain(e($this->actor->name), 'first.pdf', route('media.show', $media), '5 Mar 2026');
});

test('the kept prints action is hidden for a letter without prints', function (): void {
    $letter = tableLetter();

    letterTable()->assertActionHidden(TestAction::make('prints')->table($letter));
});

test('the upload action stores the signed scan', function (): void {
    $letter = tableLetter();
    $item = Livewire::test(FileUpload::class, ['fileRules' => ['mimes:pdf']])
        ->set('uploads', UploadedFile::fake()->create('scan.pdf', 10, 'application/pdf'))
        ->get('items')[0];

    letterTable()->call('openScan', $letter->id)->assertSet('showingScan', true)->set('scan', [$item])->call('saveScan')->assertHasNoErrors()->assertSet('showingScan', false);

    expect($letter->fresh()->getMedia(LetterMedia::SignedScan->value))->toHaveCount(1);
});

test('the upload action refuses a file type that is not a pdf or an image', function (): void {
    $letter = tableLetter();
    $item = Livewire::test(FileUpload::class, ['fileRules' => ['mimes:txt']])
        ->set('uploads', UploadedFile::fake()->create('notes.txt', 1, 'text/plain'))
        ->get('items')[0];

    letterTable()->call('openScan', $letter->id)->set('scan', [$item])->call('saveScan')->assertHasErrors('scan');

    expect($letter->fresh()->getMedia(LetterMedia::SignedScan->value))->toBeEmpty();
});

test('the upload action refuses a user who cannot update the letter', function (): void {
    $letter = tableLetter();
    Gate::policy(Letter::class, ToggledLetterPolicy::class);
    ToggledLetterPolicy::$update = false;

    letterTable()->call('openScan', $letter->id)->assertForbidden();
});

test('the upload action cannot reach a letter of another source', function (): void {
    $other = tableLetter(source: Permit::query()->create(['name' => 'Other']));

    letterTable()->call('openScan', $other->id)->assertNotFound();
});
