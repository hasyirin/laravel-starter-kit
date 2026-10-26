# Field

`<x-field>` holds the parts that every kit input shares: the label row, the help text, and the error message. All kit inputs use it, so the props on this page work on every input. The examples use `<x-input>`.

```blade
<x-input label="Email" type="email" wire:model="form.email" placeholder="name@example.com" />
```

## Required

`required` shows a red asterisk after the label and adds the `required` attribute to the input. The asterisk sits outside the label, so screen readers do not read it as part of the name of the input. They read the attribute instead.

```blade
<x-input label="Product code" wire:model="form.product_code" required />
```

## Badge

```blade
<x-input label="Middle name" wire:model="form.middle_name" badge="Optional" />
```

## Hint

The hint sits at the right end of the label row. Use it for one short fact. Put longer help text in a description.

```blade
<x-input label="Username" wire:model="form.username" hint="3 to 20 characters" />
```

The hint can also be a slot, for example a link:

```blade
<x-input.password label="Password" wire:model="form.password" autocomplete="current-password">
    <x-slot:hint>
        <flux:link :href="route('password.request')">Forgot your password?</flux:link>
    </x-slot:hint>
</x-input.password>
```

## Actions

The `actions` slot puts buttons at the right end of the label row, after the hint. Use `size="xs"` buttons, so the row keeps its height.

```blade
<x-input label="Reference" wire:model="form.receipt_reference" hint="Printed on the receipt">
    <x-slot:actions>
        <flux:button size="xs" variant="ghost" icon="printer" x-on:click="window.print()">Print</flux:button>
    </x-slot:actions>
</x-input>
```

The hint and the actions sit outside the label. So clicking an action does not move the focus to the input, and their text is not part of the name that screen readers give the input.

## Description

`description` sits under the label. It is 4 px from the label and 12 px from the input, so it reads as part of the label.

```blade
<x-input label="Display name" wire:model="form.display_name" description="Other users see this name." />
```

## Trailing Description

`description:trailing` sits 8 px under the input. If the field has an error, the error comes first. `description-trailing` also works. You can use both descriptions on one field.

```blade
<x-input
    label="Reference"
    wire:model="form.reference"
    description="Printed on the receipt."
    description:trailing="You can change it later."
/>
```

## Errors

The field shows the first error of its `wire:model` property. Without `wire:model`, it uses the `name` attribute. On the local docs page, `form.taken_email` has a sample error.

```blade
<x-input label="Email" type="email" wire:model="form.taken_email" />
```

`no-error-message` hides the message, but the input keeps the error color. Use it when another place shows the error, for example a summary at the top of the form.

```blade
<x-input label="Email" type="email" wire:model="form.taken_email" no-error-message />
```

## Wrap Another Control

Use `<x-field>` directly only for a control that has no kit input yet, such as a select. Then:

- Give the field the `name` of the property, so it shows the error.
- Add `required` to the control too. On the field, `required` only shows the asterisk.
- Add `data-input-control` to the control, so the label dims when the control is disabled.

```blade
<x-field label="Country" name="form.country" required>
    <flux:select wire:model="form.country" placeholder="Choose a country" required data-input-control>
        <flux:select.option value="MY">Malaysia</flux:select.option>
        <flux:select.option value="SG">Singapore</flux:select.option>
    </flux:select>
</x-field>
```

## Accessibility

- Give every input a `label`. If the design has no visible label, add `aria-label` to the input instead.
- Clicking the label puts the focus in the input.
- Screen readers reach the links and buttons in the hint and the actions, because they sit outside the label.
- `required` also turns on the browser's own check when the form is submitted. If you validate on the server only, add `novalidate` to the form.

## Reference

### Props

| Prop                   | Default | Description                                                                         |
|------------------------|---------|-------------------------------------------------------------------------------------|
| `label`                |         | The text above the input.                                                           |
| `required`             | `false` | Shows a red asterisk after the label. The kit inputs also add the `required` attribute. |
| `badge`                |         | A small badge after the label, such as "Optional".                                  |
| `hint`                 |         | Short text at the right end of the label row.                                       |
| `description`          |         | Help text between the label and the input.                                          |
| `description:trailing` |         | Help text below the input. `description-trailing` also works.                       |
| `no-error-message`     | `false` | Hides the error message. The input keeps the error color.                           |
| `name`                 |         | `<x-field>` only: the property whose error the field shows. The kit inputs take it from `wire:model` or `name`. |

### Slots

| Slot          | Description                                                               |
|---------------|---------------------------------------------------------------------------|
| `default`     | `<x-field>` only: the control.                                            |
| `label`       | The label, when it needs more than text.                                  |
| `badge`       | The badge, when it needs more than text.                                  |
| `hint`        | The hint, for example a link.                                             |
| `actions`     | Buttons at the right end of the label row, after the hint, such as Print. |
| `description` | The description, when it needs more than text.                            |

### Attributes

On every kit input, `class` goes to the field, so it sets the width of the label, the input, and the help text. On `<x-field>`, all other attributes go to the field too.
