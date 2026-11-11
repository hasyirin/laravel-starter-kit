# Letter Copy Fields

`<x-letter.copy-fields>` shows the carbon copies of a letter as rows. Each row is an organization, a department, or free text. The letter modal and the manual letter page use it.

```blade
<x-letter.copy-fields
    prefix="form.carbon_copies"
    :rows="[['kind' => 'text', 'target' => null, 'text' => 'Finance']]"
    :organizations="[1 => 'Acme']"
    :departments="[1 => 'Accounts']"
/>
```

The component binds each row to prefix.0.kind, prefix.0.target, and prefix.0.text. The add and remove buttons run in the browser and change the property with `$wire.$set()`. The form needs no method for them. The buttons use the `letterRows` Alpine component in `resources/js/components/letter/rows.js`.

## Disabled

`disabled` disables every input and hides the add and remove buttons.

```blade
<x-letter.copy-fields prefix="form.carbon_copies" :rows="[['kind' => 'text', 'target' => null, 'text' => 'Finance']]" disabled />
```

## Reference

### Props

| Prop            | Default | Description                                                          |
|-----------------|---------|----------------------------------------------------------------------|
| `prefix`        |         | Required. The `wire:model` path of the rows, such as form.carbon_copies. |
| `rows`          | `[]`    | The current rows. Each has `kind`, `target`, and `text`.             |
| `organizations` | `[]`    | The options of an organization row, as id and name.                  |
| `departments`   | `[]`    | The options of a department row, as id and name.                     |
| `disabled`      | `false` | Disables every input and hides the buttons.                          |
