# DataTable

`App\Livewire\DataTables\DataTable` is a [Filament table](https://filamentphp.com/docs/tables) in its own Livewire component. A subclass lists its records, columns, and actions. Filament gives the search, sort, filters, pagination, and test helpers.

```blade
<livewire:pages.local.examples.data-table />
```

A subclass gives its records with `query()` or `relationship()`, and its columns with `columns()`. Add `#[Defer]`, so that the page does not wait for the table. Lock each property that scopes the records, because the browser can change a public property:

```php
#[Defer]
class CountryDataTable extends DataTable
{
    protected function query(): Builder
    {
        return Country::query();
    }

    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => $this->can('create', Country::class))
                ->action($this->to(...)),
        ];
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->label(__('inputs.name'))->searchable()->sortable(),
            TimestampColumn::make('created_at'),
        ];
    }

    protected function actions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn (Country $country) => $this->can('view', $country))
                ->action($this->to(...)),

            DeleteAction::make()
                ->through(DeleteCountry::class)
                ->visible(fn (Country $country) => $this->can('delete', $country))
                ->disabled(fn (Country $country) => $country->isLocal()),
        ];
    }

    protected function to(?Country $record = null): void
    {
        $this->redirect($record ? route('countries.show', $record) : route('countries.create'), navigate: true);
    }
}
```

Make the parameter of `to()` nullable only when a create action shares the method.

## Defaults

- The row actions sit in a menu at the end of each row.
- A click on a row runs the action named `view`, or `edit` when the table has no `view` action. A row has no click when the table has neither, or when that action is hidden for the row. Override `recordAction()` to run another action, or `recordUrl()` to link each row to a page. A link wins over an action.
- `recordUrl()` does not check access. Return null when the user cannot open the record, for example `$this->can('view', $record) ? route('notes.show', $record) : null`.
- The browser cannot ask for a page size outside the options of the table, so it cannot load every row.
- An empty table shows `common/records.none`.
- While a table with `#[Defer]` loads, the page shows a skeleton.
- Dark mode follows Flux, and `resources/css/app.css` gives the cells the padding of Flux.

Search, sort, and filters are not kept in the URL or the session. Filament keeps only the page size in the session.

## Scope to a Parent

Use `relationship()` for the records of a parent:

```php
#[Locked]
public Notebook $notebook;

protected function relationship(): HasMany
{
    return $this->notebook->notes();
}
```

## Delete

Use `App\Filament\Actions\DeleteAction`, and name the Action that does the work with `through()`. It asks for a confirmation, runs the Action, and shows a Flux toast. Without `through()`, it fails when it runs. A test fails when a class imports the delete action of Filament, because that one skips the Actions. Always add `visible()` with the ability.

## Open a Modal

An action opens a [modal](modal.md) with an event, and the table renders again when the modal saves:

```php
protected function actions(): array
{
    return [
        EditAction::make()
            ->visible(fn (Note $note) => $this->can('update', $note))
            ->action(fn (Note $note) => $this->dispatch('show', id: $note->id)->to(NoteModal::class)),
    ];
}

protected function getListeners(): array
{
    return [...parent::getListeners(), NoteModal::event() => '$refresh'];
}
```

## Add to the Table

Override `table()` for anything else that Filament offers, and call the parent:

```php
public function table(Table $table): Table
{
    return parent::table($table)->defaultSort('created_at', 'desc');
}
```

## Test a Table

Use the helpers of Filament: `assertCanSeeTableRecords()`, `searchTable()`, `sortTable()`, and `callAction()`. See `tests/Feature/Livewire/DataTables/DataTableTest.php`.

## Reference

| Hook                         | Default                                             | Description                                                                   |
| ---------------------------- | --------------------------------------------------- | ----------------------------------------------------------------------------- |
| `DataTable::columns()`       | Required                                            | The columns.                                                                  |
| `DataTable::query()`         | Null                                                | The query for the records.                                                    |
| `DataTable::relationship()`  | Null                                                | The relation for the records of a parent.                                     |
| `DataTable::filters()`       | None                                                | The filters.                                                                  |
| `DataTable::headerActions()` | None                                                | The actions above the table.                                                  |
| `DataTable::actions()`       | None                                                | The actions of each row.                                                      |
| `DataTable::recordAction()`  | `view`, else `edit`, when it is visible for the row | The action that a row click runs.                                             |
| `DataTable::recordUrl()`     | Null                                                | The page that a row links to.                                                 |
| `DataTable::paginated()`     | True                                                | Set it to false to show all rows on one page. Use this only for a short list. |

The presets are `App\Filament\Actions\CreateAction`, `App\Filament\Actions\ViewAction`, `App\Filament\Actions\EditAction`, `App\Filament\Actions\DeleteAction`, and `App\Filament\Tables\Columns\TimestampColumn`.
