<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Symfony\Component\Finder\Finder;
use Tests\Fixtures\Livewire\DataTables\NoteDataTable;
use Tests\Fixtures\Livewire\DataTables\PlainNoteDataTable;
use Tests\Fixtures\Livewire\NoteModal;
use Tests\Fixtures\Models\Note;
use Tests\Fixtures\Models\Notebook;
use Tests\Fixtures\Policies\NotePolicy;

beforeEach(function (): void {
    Schema::create('notebooks', function (Blueprint $table): void {
        $table->id();
        $table->boolean('accepts_notes')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('notes', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('notebook_id');
        $table->string('title');
        $table->boolean('hidden')->default(false);
        $table->boolean('locked')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Relation::morphMap(['notebook' => Notebook::class, 'note' => Note::class]);
    Gate::policy(Note::class, NotePolicy::class);

    $this->actingAs(User::factory()->create());
});

test('a table shows only the records of its relationship', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak']);
    $other = Notebook::query()->create()->notes()->create(['title' => 'Other']);

    Livewire::test(NoteDataTable::class, ['notebook' => $notebook])
        ->assertCanSeeTableRecords([$note])
        ->assertCanNotSeeTableRecords([$other]);
});

test('a table can search and sort its columns and ignores a sort on an unknown column', function (): void {
    $notebook = Notebook::query()->create();
    $apple = $notebook->notes()->create(['title' => 'Apple']);
    $pear = $notebook->notes()->create(['title' => 'Pear']);

    Livewire::test(NoteDataTable::class, ['notebook' => $notebook])
        ->searchTable('Pea')
        ->assertCanSeeTableRecords([$pear])
        ->assertCanNotSeeTableRecords([$apple])
        ->searchTable('')
        ->sortTable('title', 'desc')
        ->assertCanSeeTableRecords([$pear, $apple], inOrder: true)
        ->set('tableSort', 'locked:desc')
        ->assertOk();
});

test('the browser cannot ask for a page size outside the options', function (mixed $pageSize): void {
    $notebook = Notebook::query()->create();
    collect(range(1, 12))->each(fn (int $number) => $notebook->notes()->create(['title' => "Note {$number}"]));

    $table = Livewire::test(NoteDataTable::class, ['notebook' => $notebook])->set('tableRecordsPerPage', $pageSize);

    expect($table->instance()->getTableRecords()->count())->toBe(10);
})->with(['all' => ['all'], 'too many' => [1000]]);

test('the browser cannot change the parent of a table', function (): void {
    Livewire::test(NoteDataTable::class, ['notebook' => Notebook::query()->create()])
        ->set('notebook', 2);
})->throws(CannotUpdateLockedPropertyException::class);

test('a delete runs the action, shows one toast, and sends no Filament notification', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak']);

    Livewire::test(NoteDataTable::class, ['notebook' => $notebook])
        ->callAction(TestAction::make('delete')->table($note))
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === __('common/records.deleted'))
        ->assertNotDispatched('notificationSent')
        ->assertCanNotSeeTableRecords([$note]);

    expect(Note::withTrashed()->sole()->title)->toBe('Deleted by an Action')
        ->and(session('filament.notifications'))->toBeEmpty();
});

test('a delete without the ability is hidden and refused on the server', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak', 'locked' => true]);

    Livewire::test(NoteDataTable::class, ['notebook' => $notebook])
        ->assertActionHidden(TestAction::make('delete')->table($note))
        ->call('mountAction', 'delete', [], ['table' => true, 'recordKey' => (string) $note->id])
        ->call('callMountedAction');

    expect($note->refresh()->title)->toBe('Leak')
        ->and($note->trashed())->toBeFalse();
});

test('a delete can be turned off for one row', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Protected']);

    Livewire::test(NoteDataTable::class, ['notebook' => $notebook])
        ->assertActionDisabled(TestAction::make('delete')->table($note));
});

test('a delete without an action fails loudly', function (): void {
    $note = Notebook::query()->create()->notes()->create(['title' => 'Leak']);

    Livewire::test(PlainNoteDataTable::class, ['withDeleteAction' => true])
        ->callAction(TestAction::make('delete')->table($note));
})->throws(LogicException::class);

test('a row click opens the view action of the table', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak']);

    $table = Livewire::test(NoteDataTable::class, ['notebook' => $notebook])->instance()->getTable();

    expect($table->getRecordAction($note))->toBe('view');
});

test('a row the user cannot view has no row click', function (): void {
    $notebook = Notebook::query()->create();
    $hidden = $notebook->notes()->create(['title' => 'Leak', 'hidden' => true]);
    $visible = $notebook->notes()->create(['title' => 'Note']);

    $table = Livewire::test(NoteDataTable::class, ['notebook' => $notebook])->instance()->getTable();

    expect($table->getRecordAction($hidden))->toBeNull()
        ->and($table->getRecordAction($visible))->toBe('view');
});

test('a row click opens the edit action when the table has no view action', function (): void {
    $notebook = Notebook::query()->create();
    $locked = $notebook->notes()->create(['title' => 'Leak', 'locked' => true]);
    $open = $notebook->notes()->create(['title' => 'Note']);

    $table = Livewire::test(PlainNoteDataTable::class, ['withEditAction' => true])->instance()->getTable();

    expect($table->getRecordAction($locked))->toBeNull()
        ->and($table->getRecordAction($open))->toBe('edit');
});

test('a table without a view or edit action has no row click', function (): void {
    $note = Notebook::query()->create()->notes()->create(['title' => 'Leak']);

    $table = Livewire::test(PlainNoteDataTable::class)->instance()->getTable();

    expect($table->getRecordAction($note))->toBeNull();
});

test('a table can link each row to a page', function (): void {
    $note = Notebook::query()->create()->notes()->create(['title' => 'Leak']);

    $table = Livewire::test(PlainNoteDataTable::class, ['url' => '/notes/1'])->instance()->getTable();

    expect($table->getRecordUrl($note))->toBe('/notes/1');
});

test('an empty table shows the translated heading', function (): void {
    Livewire::test(NoteDataTable::class, ['notebook' => Notebook::query()->create()])
        ->assertSeeText(__('common/records.none'));
});

test('a timestamp column shows the time in the format of the user', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak']);

    Livewire::test(NoteDataTable::class, ['notebook' => $notebook])
        ->assertTableColumnFormattedStateSet('created_at', Preferences::current()->datetime($note->created_at), $note);
});

test('a saved modal refreshes the table', function (): void {
    $notebook = Notebook::query()->create();
    $table = Livewire::test(NoteDataTable::class, ['notebook' => $notebook]);

    $note = $notebook->notes()->create(['title' => 'Leak']);

    $table->dispatch(NoteModal::event())->assertCanSeeTableRecords([$note]);
});

test('the create and view actions open the modal', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak']);

    Livewire::test(NoteDataTable::class, ['notebook' => $notebook])
        ->callAction(TestAction::make('create')->table())
        ->assertDispatchedTo(NoteModal::class, 'show')
        ->callAction(TestAction::make('view')->table($note))
        ->assertDispatchedTo(NoteModal::class, 'show', id: $note->id);
});

test('no class imports the delete action of Filament, because it skips the Actions', function (): void {
    $offenders = collect(iterator_to_array(Finder::create()->files()->in(app_path())->name('*.php')))
        ->filter(fn (SplFileInfo $file): bool => preg_match('/(?<!App\\\\)Filament\\\\Actions\\\\DeleteAction\b/', $file->getContents()) === 1)
        ->keys();

    expect($offenders)->toBeEmpty();
});
