# Letter Data Fields

`<x-letter.data-fields>` shows one input for each field of a letter type. The user fills in the letter data with it. The letter form and the manual letter page use it.

```blade
@php
    use App\Domain\Shared\Letter\Support\Field;

    $fields = [
        Field::text('title')->label('Title'),
        Field::date('issued_on'),
        Field::number('amount')->optional(),
    ];
@endphp

<x-letter.data-fields :$fields prefix="form.data" :values="$form['data'] ?? []" />
```

Each field becomes the kit input of its type, bound to prefix.name:

| Field type | Input                                                       |
|------------|-------------------------------------------------------------|
| Text       | [`<x-input>`](input.md)                                     |
| Lines      | [`<x-textarea>`](textarea.md)                               |
| Date       | [`<x-input.date>`](input.date.md)                           |
| Number     | [`<x-input.number>`](input.number.md)                       |
| Yes or no  | [`<x-switch>`](switch.md)                                   |
| Choice     | [`<x-select>`](select.md) with the options of the field     |
| Image      | [`<x-file-upload>`](file-upload.md) for one raster image of at most 10 MB |
| List       | One [`<x-input>`](input.md) for each row                    |
| Table      | The columns of the field, repeated for each row             |
| Group      | A switch that shows the fields of the group                 |

The label is the label of the field. If the field has no label, the label is its name as a heading. A field that is not optional shows the required mark.

## Values

`values` holds the current data, so that the component knows how many rows to show and which groups are on. Pass the same array that `prefix` points to.

```html
<x-letter.data-fields :$fields prefix="form.data" :values="$form->data" />
```

## Locked Fields

`locked` lists the names of fields that the user cannot change. The component disables their inputs. A locked group, list, or table is disabled with everything in it, and has no add or remove buttons.

```blade
@php
    use App\Domain\Shared\Letter\Support\Field;

    $fields = [Field::text('title'), Field::text('reference')];
@endphp

<x-letter.data-fields :$fields prefix="form.data" :values="$form['data'] ?? []" :locked="['reference']" />
```

`disabled` on a parent card disables all the inputs too.

## Groups

A group shows a switch with the label of the group. The switch is bound to prefix._groups.name. The component shows the fields of the group only when this key is true. It uses `wire:model.live`, so the fields appear at once.

```blade
@php
    use App\Domain\Shared\Letter\Support\Field;

    $fields = [Field::group('guarantor', 'Add a guarantor', [Field::text('name'), Field::text('phone')])];
@endphp

<x-letter.data-fields :$fields prefix="form.data" :values="['_groups' => ['guarantor' => true]]" />
```

The fields of the group are bound to prefix.name.child. A group inside a group keeps its switch at prefix.outer._groups.inner.

The `_groups` key is not letter data. The form must remove it before it validates and saves the data. The data rules treat a group with no filled field as absent. So the form must also refuse a group whose switch is on and whose fields are all empty.

## Lists and Tables

A list is bound to prefix.name.0, prefix.name.1, and so on. A table is bound to prefix.name.0.column. Each table row shows the columns of the table, and a column can be any field type.

Every row has a remove button, and the field has an add button. The buttons run in the browser and change the property with `$wire.$set()`. The form needs no method for them. A new table row has `null` in every column. A table cannot have an image column: `Field::table()` throws a `LogicException`, because an upload inside rows that change their index could show the wrong file.

```blade
@php
    use App\Domain\Shared\Letter\Support\Field;

    $fields = [
        Field::list('attachments'),
        Field::table('lines', [Field::text('item'), Field::number('quantity')]),
    ];
@endphp

<x-letter.data-fields :$fields prefix="form.data" :values="['attachments' => ['Receipt'], 'lines' => [['item' => 'Pen', 'quantity' => 2]]]" />
```

## Images

The image input is `<x-file-upload>` without `multiple`. The property holds the list of items of the file, not a uuid. The form must convert it to the value that the letter data stores.

## Reference

### Props

| Prop       | Default | Description                                                                                       |
|------------|---------|---------------------------------------------------------------------------------------------------|
| `fields`   | `[]`    | The list of `Field` objects to show.                                                              |
| `prefix`   |         | Required. The `wire:model` path of the data, such as form.data.                                 |
| `values`   | `[]`    | The current data at `prefix`. It sets the rows and the groups that are on.                        |
| `locked`   | `[]`    | The names of the fields that the user cannot change.                                              |
| `disabled` | `false` | Disables every input. It follows `disabled` on the card around it.                                |
| `grouped`  | `false` | Used by the component for the fields of a group and a table row. Their required mark is not shown. |

### Attributes

`class` goes to the element around the inputs.
