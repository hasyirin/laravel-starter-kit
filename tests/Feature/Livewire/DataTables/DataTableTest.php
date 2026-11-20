<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use App\Livewire\DataTables\DataTable;
use App\Livewire\Pages\Local\Examples\DataTable as ExampleDataTable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Symfony\Component\Finder\Finder;
use Tests\Fixtures\Livewire\DataTables\CursorPageNoteDataTable;
use Tests\Fixtures\Livewire\DataTables\HookedNoteDataTable;
use Tests\Fixtures\Livewire\DataTables\NamedPageNoteDataTable;
use Tests\Fixtures\Livewire\DataTables\NotebookParcelDataTable;
use Tests\Fixtures\Livewire\DataTables\NoteDataTable;
use Tests\Fixtures\Livewire\DataTables\PageHookedNoteDataTable;
use Tests\Fixtures\Livewire\DataTables\ParentPageHookedNoteDataTable;
use Tests\Fixtures\Livewire\DataTables\PlainNoteDataTable;
use Tests\Fixtures\Livewire\DataTables\SimplePageNoteDataTable;
use Tests\Fixtures\Livewire\DataTables\TernaryNoteDataTable;
use Tests\Fixtures\Livewire\NoteModal;
use Tests\Fixtures\Models\Note;
use Tests\Fixtures\Models\Notebook;
use Tests\Fixtures\Models\Parcel;
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
        $table->foreignUuid('parcel_id')->nullable();
        $table->string('title');
        $table->boolean('hidden')->default(false);
        $table->boolean('locked')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('parcels', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->string('label')->nullable();
        $table->timestamps();
    });

    Schema::create('notebook_parcel', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('notebook_id');
        $table->foreignUuid('parcel_id');
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

/**
 * @return Collection<int, array{sql: string, bindings: array<int, mixed>}>
 */
function listenToQueries(): Collection
{
    $queries = collect();
    DB::listen(fn (QueryExecuted $query) => $queries->push(['sql' => $query->sql, 'bindings' => $query->bindings]));

    return $queries;
}

/**
 * @param  Collection<int, array{sql: string, bindings: array<int, mixed>}>  $queries
 */
function expectNoQueryWith(Collection $queries, mixed $value): void
{
    $bindings = $queries->pluck('bindings')->flatten(1);

    expect($bindings)->not->toContain($value)
        ->and($bindings->filter(fn (mixed $binding): bool => is_array($binding)))->toBeEmpty();

    if (is_string($value) && strlen($value) > 2) {
        expect($queries->pluck('sql')->filter(fn (string $sql): bool => str_contains($sql, $value)))->toBeEmpty();
    }
}

test('a row or column action with a malformed record key mounts nothing and sends the key to no query', function (string $key): void {
    $notebook = Notebook::query()->create();
    $notebook->notes()->create(['title' => 'Leak']);
    $table = Livewire::test(NoteDataTable::class, ['notebook' => $notebook]);
    $queries = listenToQueries();

    $table->call('mountAction', 'delete', [], ['table' => true, 'recordKey' => $key])
        ->assertOk()
        ->assertSet('mountedActions', [])
        ->call('callTableColumnAction', 'title', $key)
        ->assertOk();

    expectNoQueryWith($queries, $key);
})->with([
    'letters' => ['abc'],
    'more digits than a bigint holds' => ['99999999999999999999'],
]);

test('a row or column action with a record key that is not text mounts nothing', function (): void {
    $notebook = Notebook::query()->create();
    $notebook->notes()->create(['title' => 'Leak']);
    $table = Livewire::test(NoteDataTable::class, ['notebook' => $notebook]);
    $queries = listenToQueries();

    $table->call('mountAction', 'delete', [], ['table' => true, 'recordKey' => [1]])
        ->assertOk()
        ->assertSet('mountedActions', [])
        ->call('callTableColumnAction', 'title', [1])
        ->assertOk();

    expectNoQueryWith($queries, [1]);
});

test('a row action on a missing record mounts nothing', function (): void {
    Livewire::test(NoteDataTable::class, ['notebook' => Notebook::query()->create()])
        ->call('mountAction', 'delete', [], ['table' => true, 'recordKey' => '999'])
        ->assertOk()
        ->assertSet('mountedActions', []);
});

test('a table of array records runs its row actions', function (): void {
    Livewire::withoutLazyLoading();

    Livewire::test(ExampleDataTable::class)
        ->call('mountAction', 'view', [], ['table' => true, 'recordKey' => '1'])
        ->assertDispatched('toast-show');
});

test('a table of pivot rows checks the record key against the pivot key', function (): void {
    $notebook = Notebook::query()->create();
    $parcel = Parcel::query()->create(['label' => 'Box']);
    $notebook->belongsToMany(Parcel::class, 'notebook_parcel')->attach($parcel);
    $pivotKey = (string) DB::table('notebook_parcel')->value('id');
    $table = Livewire::test(NotebookParcelDataTable::class, ['notebook' => $notebook]);
    $queries = listenToQueries();

    $table->call('mountAction', 'view', [], ['table' => true, 'recordKey' => 'abc'])
        ->assertSet('mountedActions', [])
        ->assertNotDispatched('opened')
        ->call('mountAction', 'view', [], ['table' => true, 'recordKey' => $pivotKey])
        ->assertDispatched('opened', id: $parcel->id);

    expectNoQueryWith($queries, 'abc');
});

test('a relation filter with a malformed key keeps no rows and sends the key to no query', function (Closure $filter, string $key): void {
    Notebook::query()->create()->notes()->create(['title' => 'Leak']);
    $table = Livewire::test(PlainNoteDataTable::class);
    $queries = listenToQueries();

    $filter($table, $key);

    $table->assertOk()->assertCountTableRecords(0);
    expectNoQueryWith($queries, $key);
})->with([
    'one value' => [fn (Testable $table, string $key) => $table->set('tableFilters.notebook.value', $key)],
    'one value applied from the filter form' => [fn (Testable $table, string $key) => $table->set('tableDeferredFilters.notebook.value', $key)->call('applyTableFilters')],
    'only malformed values' => [fn (Testable $table, string $key) => $table->set('tableFilters.notebooks.values', [$key, '1.5'])],
    'a uuid relation' => [fn (Testable $table, string $key) => $table->set('tableFilters.parcel.value', $key)],
])->with([
    'letters' => ['abc'],
    'more digits than a bigint holds' => ['99999999999999999999'],
]);

test('a relation filter keeps the rows of its valid keys and drops the malformed ones', function (): void {
    $notebook = Notebook::query()->create();
    $kept = $notebook->notes()->create(['title' => 'Kept']);
    $other = Notebook::query()->create()->notes()->create(['title' => 'Other']);
    $table = Livewire::test(PlainNoteDataTable::class);
    $queries = listenToQueries();

    $table->set('tableFilters.notebooks.values', [(string) $notebook->id, 'abc'])
        ->assertCanSeeTableRecords([$kept])
        ->assertCanNotSeeTableRecords([$other])
        ->set('tableFilters.notebooks.values', [null, (string) $notebook->id])
        ->assertCanSeeTableRecords([$kept])
        ->assertCanNotSeeTableRecords([$other])
        ->set('tableFilters.notebooks.values', [])
        ->set('tableFilters.notebook.value', (string) $notebook->id)
        ->assertCanSeeTableRecords([$kept])
        ->assertCanNotSeeTableRecords([$other]);

    expectNoQueryWith($queries, 'abc');
});

test('a relation filter on a uuid model keeps the rows of a valid uuid', function (): void {
    $parcel = Parcel::query()->create(['label' => 'Box']);
    $notebook = Notebook::query()->create();
    $kept = $notebook->notes()->create(['title' => 'Kept', 'parcel_id' => $parcel->id]);
    $other = $notebook->notes()->create(['title' => 'Other']);

    Livewire::test(PlainNoteDataTable::class)
        ->set('tableFilters.parcel.value', $parcel->id)
        ->assertCanSeeTableRecords([$kept])
        ->assertCanNotSeeTableRecords([$other]);
});

test('the empty option of a relation filter keeps the rows without a related record', function (): void {
    $deleted = Notebook::query()->create();
    $orphan = $deleted->notes()->create(['title' => 'Orphan']);
    $deleted->delete();
    $note = Notebook::query()->create()->notes()->create(['title' => 'Note']);

    Livewire::test(PlainNoteDataTable::class)
        ->set('tableFilters.notebooks.values', ['__empty', 'abc'])
        ->assertCanSeeTableRecords([$orphan])
        ->assertCanNotSeeTableRecords([$note]);
});

test('a select filter value of the wrong shape keeps no rows and reaches no query', function (string $property, mixed $value): void {
    Notebook::query()->create()->notes()->create(['title' => 'Apple']);
    $table = Livewire::test(PlainNoteDataTable::class);
    $queries = listenToQueries();

    $table->set($property, $value)
        ->call('applyTableFilters')
        ->assertOk()
        ->assertCountTableRecords(0);

    expectNoQueryWith($queries, $value);
})->with([
    'a nested list in an options filter' => ['tableFilters.title.values', [[1]]],
    'text for the list of an options filter' => ['tableFilters.title.values', 'x'],
    'text for the state of an options filter' => ['tableFilters.title', 'x'],
    'a nested list for one relation key' => ['tableFilters.notebook.value', [[1]]],
    'text for the list of a relation filter' => ['tableFilters.notebooks.values', 'x'],
    'text for the state of a relation filter' => ['tableFilters.notebook', 'x'],
    'a nested list in the filter form' => ['tableDeferredFilters.title.values', [[1]]],
    'text for the state in the filter form' => ['tableDeferredFilters.notebook', 'x'],
]);

test('a yes or no filter of the wrong shape goes back to its default', function (string $property, mixed $value): void {
    $kept = Notebook::query()->create()->notes()->create(['title' => 'Kept']);
    $deleted = Notebook::query()->create()->notes()->create(['title' => 'Deleted']);
    $deleted->delete();

    Livewire::test(TernaryNoteDataTable::class)
        ->set($property, $value)
        ->assertOk()
        ->assertCanSeeTableRecords([$kept])
        ->assertCanNotSeeTableRecords([$deleted]);
})->with([
    'a nested list for the value' => ['tableFilters.trashed.value', [[1]]],
    'text for the state' => ['tableFilters.trashed', 'x'],
    'text that is not yes or no' => ['tableFilters.trashed.value', 'abc'],
    'a number that is not yes or no' => ['tableFilters.trashed.value', 2],
    'a decimal' => ['tableFilters.trashed.value', 1.5],
]);

test('an options filter drops an empty value', function (string $property, array $values, int $count): void {
    $notebook = Notebook::query()->create();
    $notebook->notes()->create(['title' => 'Apple']);
    $notebook->notes()->create(['title' => 'Pear']);

    Livewire::test(PlainNoteDataTable::class)
        ->set($property, $values)
        ->call('applyTableFilters')
        ->assertOk()
        ->assertCountTableRecords($count);
})->with([
    'only null' => ['tableFilters.title.values', [null], 2],
    'null and an option' => ['tableFilters.title.values', [null, 'Apple'], 1],
    'null in the filter form' => ['tableDeferredFilters.title.values', [null, 'Apple'], 1],
]);

test('the browser cannot call the column action of Filament behind the guard', function (): void {
    Livewire::test(PlainNoteDataTable::class)->call('callFilamentTableColumnAction', 'title', [1]);
})->throws(MethodNotFoundException::class);

test('the browser cannot call the filter update of Filament behind the cleaning', function (): void {
    Livewire::test(PlainNoteDataTable::class)
        ->call('updatedFilamentTableFilters')
        ->assertForbidden();
});

test('a yes or no filter on a uuid relation keeps its no value', function (): void {
    $notebook = Notebook::query()->create();
    $labelled = $notebook->notes()->create(['title' => 'Labelled', 'parcel_id' => Parcel::query()->create(['label' => 'Box'])->id]);
    $unlabelled = $notebook->notes()->create(['title' => 'Unlabelled', 'parcel_id' => Parcel::query()->create()->id]);

    Livewire::test(TernaryNoteDataTable::class)
        ->set('tableFilters.labelled.value', '0')
        ->assertCanSeeTableRecords([$unlabelled])
        ->assertCanNotSeeTableRecords([$labelled]);
});

test('a table that defines its own updated hook still cleans its filters', function (): void {
    Notebook::query()->create()->notes()->create(['title' => 'Apple']);

    Livewire::test(HookedNoteDataTable::class)
        ->set('tableFilters.title.values', [[1]])
        ->assertOk()
        ->assertCountTableRecords(0)
        ->assertSet('updatedProperties', ['tableFilters.title.values']);
});

test('a search that is not text is ignored and reaches no query', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Apple']);
    $table = Livewire::test(NoteDataTable::class, ['notebook' => $notebook]);
    $queries = listenToQueries();

    $table->set('tableSearch', [1])
        ->assertOk()
        ->assertCanSeeTableRecords([$note]);

    expectNoQueryWith($queries, [1]);
});

/**
 * @return Collection<int, Note>
 */
function createTwelveNotes(): Collection
{
    $notebook = Notebook::query()->create();

    return collect(range(1, 12))->map(fn (int $number): Note => $notebook->notes()->create(['title' => "Note {$number}"]));
}

/**
 * @param  Collection<int, array{sql: string, bindings: array<int, mixed>}>  $queries
 */
function expectOffsets(Collection $queries, int $offset): void
{
    $offsets = $queries->pluck('sql')
        ->filter(fn (string $sql): bool => str_contains($sql, ' offset '))
        ->map(fn (string $sql): string => str($sql)->afterLast(' offset ')->value())
        ->values();

    expect($offsets)->not->toBeEmpty()
        ->each->toBe((string) $offset);
}

test('a page number that is not a whole number from 1 to 2147483647 opens page 1', function (Closure $open, mixed $page): void {
    $notes = createTwelveNotes();
    $queries = listenToQueries();

    $open($page)
        ->assertOk()
        ->assertSet('paginators.page', 1, strict: true)
        ->assertCanSeeTableRecords($notes->take(10))
        ->assertCanNotSeeTableRecords($notes->skip(10));

    expectOffsets($queries, 0);
})->with([
    'in the url' => fn (mixed $page): Testable => Livewire::withQueryParams(['page' => $page])->test(PlainNoteDataTable::class),
    'through gotoPage' => fn (mixed $page): Testable => Livewire::test(PlainNoteDataTable::class)->call('gotoPage', $page),
    'through setPage' => fn (mixed $page): Testable => Livewire::test(PlainNoteDataTable::class)->call('setPage', $page),
    'through a paginators update' => fn (mixed $page): Testable => Livewire::test(PlainNoteDataTable::class)->set('paginators.page', $page),
])->with([
    'text' => ['abc'],
    'zero' => ['0'],
    'negative' => ['-1'],
    'a fraction' => ['1.5'],
    'a large exponent' => ['1e30'],
    'an exponent beyond the integer range' => ['9.3e18'],
    'a float beyond the integer range' => [9.3e18],
    'the largest integer' => ['9223372036854775807'],
    'one beyond the limit' => ['2147483648'],
    'beyond the largest integer' => ['99999999999999999999'],
    'a list' => [[2]],
]);

test('a paginators update that is not a list of pages opens page 1', function (): void {
    $notes = createTwelveNotes();

    Livewire::test(PlainNoteDataTable::class)
        ->set('paginators', 'abc')
        ->assertOk()
        ->assertSet('paginators', ['page' => 1], strict: true)
        ->assertCanSeeTableRecords($notes->take(10));
});

test('the page buttons move on from a bad page number sent in the same request', function (string $method, int $page): void {
    createTwelveNotes();

    Livewire::test(PlainNoteDataTable::class)
        ->update(
            calls: [['method' => $method, 'params' => [], 'path' => '']],
            updates: ['paginators.page' => 'abc'],
        )
        ->assertOk()
        ->assertSet('paginators.page', $page, strict: true);
})->with([
    'next page' => ['nextPage', 2],
    'previous page' => ['previousPage', 1],
]);

test('the next page after page 2147483647 is page 1', function (): void {
    createTwelveNotes();

    Livewire::test(PlainNoteDataTable::class)
        ->call('gotoPage', 2147483647)
        ->call('nextPage')
        ->assertOk()
        ->assertSet('paginators.page', 1, strict: true);
});

test('page 2 shows the rows after the first ten', function (): void {
    $notes = createTwelveNotes();
    $queries = listenToQueries();

    Livewire::withQueryParams(['page' => '2'])->test(PlainNoteDataTable::class)
        ->assertOk()
        ->assertSet('paginators.page', 2, strict: true)
        ->assertCanSeeTableRecords($notes->skip(10))
        ->assertCanNotSeeTableRecords($notes->take(10));

    expectOffsets($queries, 10);
});

test('page 2147483647 opens as an empty page', function (): void {
    $notes = createTwelveNotes();
    $queries = listenToQueries();

    Livewire::withQueryParams(['page' => '2147483647'])->test(PlainNoteDataTable::class)
        ->assertOk()
        ->assertSet('paginators.page', 2147483647, strict: true)
        ->assertCanNotSeeTableRecords($notes)
        ->assertSee(__('common/records.none'));

    expectOffsets($queries, 21474836460);
});

test('a table that defines its own updated hook still opens page 1 for a bad page number', function (): void {
    createTwelveNotes();

    Livewire::test(HookedNoteDataTable::class)
        ->set('paginators', 'abc')
        ->assertOk()
        ->assertSet('paginators', ['page' => 1], strict: true)
        ->assertSet('updatedProperties', ['paginators']);
});

test('the browser cannot call the page methods behind the page check', function (string $method): void {
    Livewire::test(PlainNoteDataTable::class)->call($method, '9.3e18');
})->throws(MethodNotFoundException::class)->with(['setFilamentPage', 'setLivewirePage', 'resetLivewirePage', 'paginateFilamentTableQuery']);

test('a table with its own page name or simple pagination opens page 1 for a bad page number', function (Closure $open, string $table, string $pageName): void {
    $notes = createTwelveNotes();
    $queries = listenToQueries();

    $open($table, $pageName)
        ->assertOk()
        ->assertSet("paginators.{$pageName}", 1, strict: true)
        ->assertCanSeeTableRecords($notes->take(10))
        ->assertCanNotSeeTableRecords($notes->skip(10));

    expectOffsets($queries, 0);
})->with([
    'in the url' => fn (string $table, string $pageName): Testable => Livewire::withQueryParams([$pageName => '9223372036854775807'])->test($table),
    'through gotoPage' => fn (string $table, string $pageName): Testable => Livewire::test($table)->call('gotoPage', '9.3e18', $pageName),
    'through a paginators update' => fn (string $table, string $pageName): Testable => Livewire::test($table)->set("paginators.{$pageName}", '2147483648'),
])->with([
    'own page name' => [NamedPageNoteDataTable::class, 'notesPage'],
    'simple pagination' => [SimplePageNoteDataTable::class, 'page'],
]);

test('a table with its own page name or simple pagination opens page 2', function (string $table, string $pageName): void {
    $notes = createTwelveNotes();
    $queries = listenToQueries();

    Livewire::withQueryParams([$pageName => '2'])->test($table)
        ->assertOk()
        ->assertSet("paginators.{$pageName}", 2, strict: true)
        ->assertCanSeeTableRecords($notes->skip(10))
        ->assertCanNotSeeTableRecords($notes->take(10));

    expectOffsets($queries, 10);
})->with([
    'own page name' => [NamedPageNoteDataTable::class, 'notesPage'],
    'simple pagination' => [SimplePageNoteDataTable::class, 'page'],
]);

test('a table that defines its own paginators hook still opens page 1 for bad paginators', function (string $table): void {
    $notes = createTwelveNotes();

    Livewire::test($table)
        ->set('paginators', 'abc')
        ->assertOk()
        ->assertSet('paginators', ['page' => 1], strict: true)
        ->assertSet('hookedPageNames', [null])
        ->assertCanSeeTableRecords($notes->take(10));
})->with([
    'without the parent hook' => [PageHookedNoteDataTable::class],
    'with the parent hook' => [ParentPageHookedNoteDataTable::class],
]);

test('the browser can still reset the page', function (): void {
    createTwelveNotes();

    Livewire::test(PlainNoteDataTable::class)
        ->call('gotoPage', 2)
        ->call('resetTableSearch')
        ->assertOk()
        ->assertSet('paginators.page', 1, strict: true);
});

function nextPageCursor(Testable $component): string
{
    preg_match("/setPage\('([^']+)', 'page'\)/", $component->html(), $matches);

    expect($matches)->toHaveKey(1);

    return $matches[1];
}

test('a table with cursor pagination keeps the cursor of the next page', function (Closure $open): void {
    $notes = createTwelveNotes();
    $cursor = nextPageCursor(Livewire::test(CursorPageNoteDataTable::class));

    $open($cursor)
        ->assertOk()
        ->assertSet('paginators.page', $cursor, strict: true)
        ->assertCanSeeTableRecords($notes->skip(10))
        ->assertCanNotSeeTableRecords($notes->take(10));
})->with([
    'in the url' => fn (string $cursor): Testable => Livewire::withQueryParams(['page' => $cursor])->test(CursorPageNoteDataTable::class),
    'through setPage' => fn (string $cursor): Testable => Livewire::test(CursorPageNoteDataTable::class)->call('setPage', $cursor),
    'through a paginators update' => fn (string $cursor): Testable => Livewire::test(CursorPageNoteDataTable::class)->set('paginators.page', $cursor),
]);

test('the paginators hook of the data table has no native return type, so an app override in the Livewire docs form stays compatible', function (): void {
    expect(new ReflectionMethod(DataTable::class, 'updatedPaginators')->hasReturnType())->toBeFalse();
});
