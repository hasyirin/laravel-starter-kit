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
                ->label((string) __('countries.country'))
                ->visible(fn () => $this->can('create', Country::class))
                ->action($this->to(...)),
        ];
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->label((string) __('inputs.name'))->searchable()->sortable(),
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
- The table handles a record key that is not valid for the model like a deleted row. The row action does not open, and no query gets the key.
- A relation filter drops each value that is not a valid key. If no value stays, the filter matches no record.
- A select filter with a value of the wrong shape, such as a list for a single select, matches no record.
- A yes or no filter with a value that is not yes, no, or empty goes back to its default.
- Any other filter with a state of the wrong shape counts as empty.
- The table ignores a search that is not text.
- The date filters of the audit log and API log tables ignore a date that is not in the Y-m-d form.
- An empty table shows `common/records.none`.
- While a table with `#[Defer]` loads, the page shows a skeleton.
- Dark mode follows Flux, and `resources/css/app.css` gives the cells the padding of Flux.
- A table heading, from `heading()` in `table()`, looks like the heading of a card. It is 14px with a line height of 24px, medium weight, zinc-800, and white in dark mode. `resources/css/app.css` does this with the `fi-ta-header-heading` class of Filament. After you upgrade Filament, make sure that the class is still in `vendor/filament/tables/resources/css/container.css`.
- The buttons of Filament look like Flux buttons. `resources/css/app.css` does this. See [Button Look](#button-look).

Search, sort, and filters are not kept in the URL or the session. Filament keeps only the page size in the session.

The checks above do not cover some cases yet. The kit does not use them, so check them when an app adds one:

- An options filter on an integer, boolean, or enum column. A value that is not an option, or a value of the wrong shape, can still fail on PostgreSQL.
- A relation filter through a HasManyThrough relation. The table does not check its values.
- A relation filter that compares on a key that is not the primary key of the related model. The table does not check its values.
- Bulk actions and reordering. The table does not check their record keys.

The table cleans the filter state after the code of the app sees it. An `updated()` hook and an `afterStateUpdated()` callback get the state before the table cleans it.

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

## Show Who Acted

`App\Filament\Tables\Columns\ActColumn` shows an action of each row as [`<x-act>`](act.md) does: the name of the user, the position that the user held then, and the time. Give it the relation of the action, such as `preparer`:

```php
ActColumn::make('preparer')->label((string) __('common/acts.prepare.by')),
```

The column loads the action, its profile, user, position, and actor for the whole page, so you do not add them to the query. A row without the action has an empty cell. You cannot search or sort the column.

## Add to the Table

Override `table()` for anything else that Filament offers, and call the parent:

```php
public function table(Table $table): Table
{
    return parent::table($table)->defaultSort('created_at', 'desc');
}
```

## Button Look

`resources/css/app.css` gives the buttons of Filament the look of Flux buttons. This covers the buttons above a table and the buttons of Filament dialogs, such as the confirmation of the delete action.

| Filament button                                                  | Flux look                                                                                    |
| ---------------------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| Primary                                                          | `variant="primary"`, in the accent color                                                     |
| Danger                                                           | `variant="danger"`                                                                           |
| Success                                                          | `variant="primary" color="green"`                                                            |
| Warning                                                          | `variant="primary" color="amber"`                                                            |
| Info                                                             | `variant="primary" color="blue"`                                                             |
| A color that the app registers under a new name                  | The border and the inner highlight of `variant="primary"`, on the colors that Filament picks |
| Gray, which has no color                                         | The default, `variant="outline"`                                                             |
| Gray and not outlined, in the footer of a dialog, such as Cancel | `variant="filled"`                                                                           |
| Outlined, with a color                                           | The default variant with that color, such as `color="green"`                                 |
| Outlined, gray                                                   | The default, `variant="outline"`                                                             |
| Size md, sm, and xs                                              | Size base, sm, and xs, with a 16px icon                                                      |

The icon of a gray or outlined button takes the color of its text, as in Flux.

Success, warning, info, and outlined buttons with a color take their colors from the palette that the app registers in Filament for the color name. From that palette, the rules use the shades that Flux uses for green, amber, and blue. In dark mode, an outlined button uses shade 400 for every color. Flux uses shade 300 there for indigo and purple. So in dark mode, an outlined button of these two colors is one shade darker than in Flux.

With the default palettes of Filament, a success button has white text on shade 600 of green, as in Flux. This gives a contrast of 3.22:1. An info button has white text on shade 500 of blue, as in Flux. This gives a contrast of 3.76:1. Both are below the 4.5:1 of WCAG AA.

The rules leave these buttons as Filament draws them:

- Sizes lg and xl, and a button with no size class.
- Icon buttons, links, buttons in a button group, and toggle buttons.

The focus ring and the disabled look stay those of Filament.

The rules do not handle a button in a table row while a table with `loadingSkeleton()` loads. Filament then draws each row button as a gray block. With the rules, a gray or outlined button keeps its look and shows its label. The kit puts row actions in a menu and does not turn on the skeleton, so the kit leaves this case.

The rules copy class names and CSS variables of Filament, and the classes of the Flux button. An update of Filament or Flux can change them without notice.

| Internal                                                                                                                                  | Where the kit uses it                                                                                 | What breaks when it changes                                                      |
| ----------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- |
| `fi-btn`, `fi-size-md`, `fi-size-sm`, and `fi-size-xs` of Filament                                                                        | `app.css`: find a button that takes the look, and its size                                            | Buttons lose the whole Flux look, or other buttons get it.                       |
| `fi-color`, `fi-color-primary`, `fi-color-danger`, `fi-color-success`, `fi-color-warning`, `fi-color-info`, and `fi-outlined` of Filament | `app.css`: choose the look of a button                                                                | Buttons get the wrong colors.                                                    |
| `fi-btn-group` of Filament, and a `label` element or the `aria-pressed` attribute for a toggle button                                     | `app.css`: skip grouped buttons and toggle buttons                                                    | Grouped buttons or toggle buttons get the Flux look.                             |
| `fi-modal-footer` of Filament                                                                                                             | `app.css`: find Cancel in a dialog                                                                    | Cancel gets the outline look.                                                    |
| `fi-icon` of Filament                                                                                                                     | `app.css`: the icon size, the icon color of a gray or outlined button, and the space next to the icon | The icon gets bigger or takes the wrong color, or the space next to it is wrong. |
| `--bg`, `--hover-bg`, `--dark-bg`, `--dark-hover-bg`, `--text`, `--hover-text`, `--dark-text`, and `--dark-hover-text` of Filament        | `app.css`: the colors of primary, danger, success, warning, and info buttons                          | These buttons get the colors of Filament.                                        |
| `--color-400`, `--color-500`, `--color-600`, `--color-700`, and `--color-950` of Filament, which the `fi-color` classes set               | `app.css`: the shades of success, warning, info, and outlined buttons                                 | These buttons get the wrong colors, or no colors.                                |
| `fi-disabled` and `fi-force-enabled` of Filament, and the `disabled` attribute                                                            | `app.css`: the hover of a gray or outlined button                                                     | A disabled or loading gray or outlined button shows a hover color.               |
| The 1px ring of a gray or outlined Filament button                                                                                        | `app.css`: removed when the button has no keyboard focus                                              | The button shows a ring around its border.                                       |
| The size and variant classes of the Flux button                                                                                           | `app.css`: copied with `@apply`                                                                       | Filament buttons stop matching Flux buttons.                                     |
| The color map of the Flux button view, which sets the accent and the shades for each color                                                | `app.css`: the shades of success, warning, info, and outlined buttons                                 | These buttons stop matching Flux buttons.                                        |

Filament gives no class for the side of the icon, and the label is a text node. So the rules cannot reduce the padding on the icon side, as Flux does. Instead, each icon moves 4px into the padding on both sides, and the gap is 4px larger than in Flux.

After you upgrade Filament or Flux, compare the rules with these files. Then check the buttons of a table and of the delete confirmation.

- `vendor/livewire/flux/stubs/resources/views/flux/button/index.blade.php`
- `vendor/filament/support/resources/css/components/button.css`
- `vendor/filament/support/resources/css/utilities.css`
- `vendor/filament/support/src/View/Concerns/CanGenerateButtonHtml.php`

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

### Presets

Use these in place of the actions and columns of Filament with the same job. Each action sets its name, icon, label, and color. The create action is the exception for the label: give it the singular name of its record, such as "Country". Its plus icon already says "add". Give each one `visible()`, and `action()`, or `through()` for the delete action.

| Preset                              | Name     | Icon                       | Label                   | Color   | Notes                                                                 |
| ----------------------------------- | -------- | -------------------------- | ----------------------- | ------- | --------------------------------------------------------------------- |
| `App\Filament\Actions\CreateAction` | `create` | `heroicon-c-plus`          | Required                | Primary | For `headerActions()`. Throws when it has no label.                   |
| `App\Filament\Actions\ViewAction`   | `view`   | `heroicon-m-eye`           | `common/buttons.view`   | Primary | A row click runs it.                                                  |
| `App\Filament\Actions\EditAction`   | `edit`   | `heroicon-m-pencil-square` | `common/buttons.edit`   | Gray    | A row click runs it when the table has no `view` action.              |
| `App\Filament\Actions\DeleteAction` | `delete` | Trash                      | `common/buttons.delete` | Danger  | Asks for a confirmation and needs `through()`. See [Delete](#delete). |

| Preset                                        | Notes                                                                                                        |
| --------------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| `App\Filament\Tables\Columns\TimestampColumn` | Shows a time in the time zone and the locale of the user who reads the page, aligned to the end of the cell. |
| `App\Filament\Tables\Columns\ActColumn`       | Shows who did an action, their position then, and when. See [Show Who Acted](#show-who-acted).               |
