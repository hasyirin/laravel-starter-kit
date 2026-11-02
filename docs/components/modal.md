# Modal

A modal is a dialog in its own Livewire component. `App\Livewire\Modal` holds the open state. `App\Livewire\RecordModal` adds a form that creates or edits one record.

```blade
<livewire:pages.local.examples.modal />
```

A modal extends the base class. Its view binds the `open` property and puts the content in an `@if`, so the form and its option queries stay out of the page while the modal is closed:

```html
<div>
    <flux:modal wire:model="open">
        @if ($open)
            <form wire:submit="save">...</form>
        @endif
    </flux:modal>
</div>
```

Use `wire:submit` for the save button. Livewire disables a submit button during its request, but it has no such guard for `wire:click`.

## Open and Close

Send the `show` event to the class of the modal. Always use `to()`, because a `show` event without it opens every modal on the page. Keep one instance of a modal class on a page, because the event reaches every instance:

```php
$this->dispatch('show', id: $note->id)->to(NoteModal::class);
```

`show()` opens the modal and clears old validation errors. `close()` closes it. Cancel, Esc, and a backdrop click close the dialog in the browser, and the next request sends the new state to the server. The browser can only close a modal: a change of `open` from the browser always ends as closed, so `show()` and its checks cannot be skipped.

## Disabled

A modal uses `App\Livewire\Concerns\CanBeDisabled`, so the view gets `$disabled`. See the Disable section of `docs/livewire.md`.

```blade
<livewire:pages.local.examples.modal :disabled="true" />
```

## Record Modal

A record modal needs a model and a policy, so this page has no live example. A subclass gives four methods:

```php
/** @extends RecordModal<Note> */
class NoteModal extends RecordModal
{
    #[Locked]
    public Notebook $notebook;

    public NoteForm $form;

    public static function label(): string
    {
        return __('notes/labels.note');
    }

    protected function query(): HasMany
    {
        return $this->notebook->notes();
    }

    protected function fillForm(): void
    {
        $this->form->set($this->record);
    }

    protected function persist(): void
    {
        SaveNote::make()->execute($this->record, $this->form->values());
    }
}
```

- `query()` scopes every lookup, so an id from another parent gives 404. A new record comes from `make()` on the same query. A has-one or has-many relation sets the foreign key on the new record, so the policy can read the parent from it. A belongs-to-many or through relation does not, so override `isDisabled()` when the policy needs the parent.
- `show($id)` checks the `view` ability, or `create` for a new record.
- `save()` needs an open modal and the `update` or `create` ability. It validates, calls `persist()`, shows a toast, dispatches the saved event, and closes the modal. A second save after the modal closes gives 403.
- To go to another page after a save, call `redirect()` with `navigate: true` in `persist()`, so that the toast stays.

Listen for the saved event of the modal on the page. Each modal class has its own event:

```php
protected function getListeners(): array
{
    return [...parent::getListeners(), NoteModal::event() => '$refresh'];
}
```

The toast uses `common/records.created` and `common/records.updated`. Use `common/records.add` and `common/records.edit` for the heading. Keep delete buttons in the table, with an Action.

## Reference

### Modal

| Member | Description |
| --- | --- |
| `open` | The open state. Bind it with `wire:model`. |
| `disabled` | A prop from the parent. The view gets `$disabled`. |
| `Modal::show()` | Opens the modal and clears validation errors. It listens for the `show` event. |
| `Modal::close()` | Closes the modal. |

### Record Modal

| Member | Description |
| --- | --- |
| `recordId` | The id of the record. The browser cannot change it. Null means a new record. |
| `RecordModal::record()` | The current record, read as `$this->record`. |
| `RecordModal::label()` | The name of the record, for the toast and heading. Required. |
| `RecordModal::query()` | The scoped query for the records. Required. |
| `RecordModal::fillForm()` | Fills the form from the record. Required. |
| `RecordModal::persist()` | Saves the form to the record, with an Action. Required. |
| `RecordModal::event()` | The name of the saved event. |
| `RecordModal::save()` | Checks, validates, persists, and closes. |
