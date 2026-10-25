# Input

`<x-input>` is a text field. It puts the label, the input, the error message, and the help text in one `flux:field`. Use it instead of `flux:input` in forms.

```blade
<x-input label="Email" type="email" wire:model="form.email" placeholder="name@example.com" />
```

## Required

`required` shows a red asterisk after the label and adds the `required` attribute to the input. Screen readers skip the asterisk and read the attribute instead.

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
<x-input label="Password" type="password" wire:model="form.password" viewable>
    <x-slot:hint>
        <flux:link :href="route('password.request')">Forgot your password?</flux:link>
    </x-slot:hint>
</x-input>
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

## Prefix and Suffix

Give `prefix` or `suffix` a text, and the input group shows it in a gray box.

```blade
<x-input label="Website" wire:model="form.website" prefix="https://" suffix=".com" />
```

Give a slot to put your own element in the group, such as a select or a button.

```blade
<x-input label="Phone" wire:model="form.phone">
    <x-slot:prefix>
        <flux:select wire:model="form.country" class="max-w-fit">
            <flux:select.option value="MY">+60</flux:select.option>
            <flux:select.option value="SG">+65</flux:select.option>
        </flux:select>
    </x-slot:prefix>
</x-input>
```

## Icons and Buttons

The Flux input props work on `<x-input>`: `icon` and `icon:trailing` show an icon, and `clearable`, `copyable`, and `viewable` add a button inside the input.

```blade
<x-input label="Search" wire:model="form.search" icon="magnifying-glass" clearable placeholder="Search orders" />
<x-input label="Invite link" wire:model="form.invite_link" icon:trailing="link" copyable />
```

## Mask

`mask` limits what the user can type. `9` stands for a digit, `a` for a letter, and `*` for either.

```blade
<x-input label="Card number" wire:model="form.card_number" mask="9999 9999 9999 9999" placeholder="0000 0000 0000 0000" />
```

## Sizes

`size="sm"` makes the input 32 px high and `size="xs"` 24 px. The prefix and suffix follow. Leave `size` out for 40 px.

```blade
<x-input label="Default" wire:model="form.amount" prefix="RM" />
<x-input label="Small" wire:model="form.amount_small" size="sm" prefix="RM" />
<x-input label="Extra small" wire:model="form.amount_tiny" size="xs" prefix="RM" />
```

## Case Control

| `case`     | The user types    | The input and its property get |
|------------|-------------------|--------------------------------|
| `upper`    | `my-001`          | `MY-001`                       |
| `lower`    | `Ali@Example.COM` | `ali@example.com`              |
| `title`    | `jean-luc PICARD` | `Jean-Luc Picard`              |
| `sentence` | `hello. WORLD`    | `Hello. World`                 |

```blade
<x-input label="Upper" wire:model="form.upper" case="upper" placeholder="my-001" />
<x-input label="Lower" type="email" wire:model="form.lower" case="lower" placeholder="Ali@Example.COM" />
<x-input label="Title" wire:model="form.title" case="title" placeholder="jean-luc PICARD" />
<x-input label="Sentence" wire:model="form.sentence" case="sentence" placeholder="hello. WORLD" />
```

- The case changes while the user types, before `wire:model` or `x-model` reads the value. So the property always holds what the input shows.
- The caret stays in place. Text from an input method (IME) changes when the composition ends.
- `title` and `sentence` make every other letter lowercase, so `McDonald` becomes `Mcdonald`.
- The browser does the change. A request can still send any case, so validate the case on the server when it matters.

## Default Value

```blade
<x-input label="City" wire:model="form.city" default="Kuala Lumpur" />
```

A checkbox below the input shows "Use the default: Kuala Lumpur".

- Tick the checkbox: the input gets the default.
- Untick it: the input gets back the text from before the tick.
- Edit the input: the checkbox unticks. Type the default again: it ticks again.
- When the server changes the property, the checkbox follows.
- With `case`, the default changes case the same way as typed text.
- `wire:model` stays on the input, so every modifier works. A tick sends the value the same way as typing and leaving the field. For example, `wire:model.live.blur` sends the update when you tick the checkbox.

`default-label` replaces the label of the checkbox:

```blade
<x-input label="Postcode" wire:model="form.postcode" default="50450" default-label="Same as the head office" />
```

## Disabled and Read-Only

When the input is `disabled` or `readonly`, the default checkbox is disabled too. A disabled field also dims its label.

```blade
<x-input label="Branch" wire:model="form.branch" default="Kuala Lumpur" disabled />
<x-input label="Account number" wire:model="form.account_number" readonly />
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

## Width

`class` goes to the field, so it sets the width of the label, the input, and the help text. `input-class` goes to the wrapper of the input only.

```blade
<x-input label="State" wire:model="form.state" class="max-w-xs" />
<x-input label="Quantity" type="number" wire:model="form.quantity" input-class="max-w-32" hint="Up to 99" />
```

## Accessibility

- Give every input a `label`. If the design has no visible label, add `aria-label` instead.
- Clicking the label puts the focus in the input.
- Screen readers reach the links and buttons in the hint and the actions, because they sit outside the label.
- `required` also turns on the browser's own check when the form is submitted. If you validate on the server only, add `novalidate` to the form.

## Reference

### Props

| Prop                   | Default                    | Description                                                                       |
|------------------------|----------------------------|-----------------------------------------------------------------------------------|
| `label`                |                            | The text above the input.                                                         |
| `required`             | `false`                    | Shows a red asterisk after the label and adds the `required` attribute.           |
| `badge`                |                            | A small badge after the label, such as "Optional".                                |
| `hint`                 |                            | Short text at the right end of the label row.                                     |
| `description`          |                            | Help text between the label and the input.                                        |
| `description:trailing` |                            | Help text below the input. `description-trailing` also works.                     |
| `prefix`               |                            | Text in a box before the input.                                                   |
| `suffix`               |                            | Text in a box after the input.                                                    |
| `size`                 |                            | `sm` (32 px) or `xs` (24 px). Without it, the input is 40 px high.                |
| `case`                 |                            | `upper`, `lower`, `title`, or `sentence`. Other values throw an exception.        |
| `default`              |                            | Adds a checkbox below the input that fills in this value.                         |
| `default-label`        | "Use the default: *value*" | The label of the default checkbox.                                                |
| `input-class`          |                            | Classes for the wrapper of the input.                                             |
| `no-error-message`     | `false`                    | Hides the error message. The input keeps the error color.                         |

### Slots

| Slot          | Description                                                                 |
|---------------|-----------------------------------------------------------------------------|
| `label`       | The label, when it needs more than text.                                    |
| `badge`       | The badge, when it needs more than text.                                    |
| `hint`        | The hint, for example a link.                                               |
| `actions`     | Buttons at the right end of the label row, after the hint, such as Print.   |
| `description` | The description, when it needs more than text.                              |
| `prefix`      | Your own element before the input, such as a select. It is not in a box.    |
| `suffix`      | Your own element after the input, such as a button. It is not in a box.     |

### Attributes

All other attributes go to the input. These include `wire:model` with any modifier, `x-model`, `id`, `name`, `type`, `placeholder`, `autocomplete`, `autofocus`, `disabled`, and `readonly`.

The Flux input props also work: `icon`, `icon:trailing`, `icon:variant`, `clearable`, `copyable`, `viewable`, `expandable`, `mask`, `kbd`, `invalid`, and `loading`. Only `class` goes to the field.
