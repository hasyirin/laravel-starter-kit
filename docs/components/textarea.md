# Textarea

`<x-textarea>` is a text field for text with more than one line. It wraps `flux:textarea`.

```blade
<x-textarea label="Notes" wire:model="form.notes" />
```

It takes every [field prop](field.md).

## Rows

`rows` sets the height in lines. The default is 4. `rows="auto"` makes the textarea grow with its text.

```blade
<x-textarea label="Address" wire:model="form.address" rows="3" />
<x-textarea label="Message" wire:model="form.message" rows="auto" />
```

## Case Control

`case` changes the case while the user types, the same way as on [`<x-input>`](input.md). With `sentence`, the first letter and each letter after a full stop, a question mark, or an exclamation mark become uppercase, also on a new line. All other letters become lowercase.

```blade
<x-textarea label="Remarks" wire:model="form.remarks" case="sentence" placeholder="the parcel was left at the door. please call first" />
```

## Width

`class` goes to the field, so it sets the width of the label, the textarea, and the help text. `input-class` goes to the textarea only.

```blade
<x-textarea label="Summary" wire:model="form.summary" class="max-w-md" />
```

## Reference

### Props

| Prop          | Default | Description                                                                                                                 |
|---------------|---------|-----------------------------------------------------------------------------------------------------------------------------|
| Field props   |         | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `case`        |         | `upper`, `lower`, `title`, or `sentence`. Other values throw an exception.                                                  |
| `input-class` |         | Classes for the textarea.                                                                                                   |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |
| `default`   | The text of the textarea, for a form without Livewire.                         |

### Attributes

All other attributes go to `flux:textarea`. These include `wire:model` with any modifier, `name`, `rows`, `resize`, `placeholder`, `disabled`, `readonly`, and `invalid`. `class` goes to the field.
