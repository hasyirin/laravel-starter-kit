# Letter Data Table

`App\Livewire\DataTables\LetterDataTable` is a [DataTable](data-table.md) of the letters of one letter source. An app puts it on the page of the source. It also renders the letter modal, so the app adds one tag only.

```blade
<livewire:data-tables.letter-data-table :source="$approval" />
```

`source` is the model that owns the letters, such as an approval. The component locks it, so the browser cannot change it. The user must have the `view` ability on the source, or the table answers 403.

## Columns

| Column        | Shows                                                                          |
| ------------- | ------------------------------------------------------------------------------ |
| Letter type   | The key of the letter type as a heading.                                       |
| Reference number | The reference number. The user can search it.                               |
| Date          | The date of the letter, in the locale of the user.                             |
| Signer        | The signer, their position when the letter was saved, and the time of signing. |
| Signed scan   | A tick when the letter has a signed scan.                                      |

The newest letter is first. The table loads the source, the signer, and the media of all rows in a fixed number of queries.

## Actions

The create action sits above the table. Each other action sits in the menu of a row. Every action follows `App\Domain\Shared\Letter\Policies\LetterPolicy`, so it asks the policy of the source.

| Action             | Ability           | What it does                                                                                      |
| ------------------ | ----------------- | ------------------------------------------------------------------------------------------------- |
| Create             | `create`          | Opens the letter modal with a new letter.                                                         |
| Edit               | `update` or `print` | Opens the letter modal with the letter. A row click runs it.                                    |
| Preview            | `print`           | Opens the `letters.preview` route in a new tab. A preview has no reference number and keeps nothing. |
| Print              | `print`           | Downloads the PDF with `PrintLetter`. A letter that cannot print shows a toast with the reason.    |
| Upload signed scan | `update`          | Opens a dialog to upload one PDF or image as the signed scan.                                    |
| Kept prints        | `view`            | Lists the kept prints with the printer and the time, each linked to the `media.show` route. Shown only when the letter has kept prints. |
| Delete             | `delete`          | Asks for a confirmation, then runs `DeleteLetter`. The letter is soft deleted.                    |

The table listens to `App\Livewire\Components\LetterModal::event()` and loads again when the modal saves.

## Test a Table

```php
Livewire::withoutLazyLoading()
    ->test(LetterDataTable::class, ['source' => $approval])
    ->assertCanSeeTableRecords([$letter])
    ->callAction(TestAction::make('print')->table($letter))
    ->assertFileDownloaded();
```

See `tests/Feature/Livewire/DataTables/LetterDataTableTest.php`.

## Reference

### Properties

| Property | Description                                                          |
| -------- | -------------------------------------------------------------------- |
| `source` | Required. The model that owns the letters. Locked.                   |
