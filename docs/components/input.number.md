# Number

`<x-input.number>` is a field for numbers. The input shows the number with thousands separators and a fixed number of decimals, for example `1,234.50`. Its property gets the plain number, `1234.50`.

```blade
<x-input.number label="Amount" wire:model="form.amount" prefix="RM" />
```

It takes every [field prop](field.md). `prefix`, `suffix`, `size`, `default`, `default-label`, and `input-class` work the same way as on [`<x-input>`](input.md).

## Decimals

`decimals` sets the number of decimals. The default is 2. With `decimals="0"`, the user can type whole numbers only.

```blade
<x-input.number label="Quantity" wire:model="form.quantity" decimals="0" />
<x-input.number label="Latitude" wire:model="form.latitude" decimals="5" />
```

## Separators

`comma` (default `,`) is the thousands separator, and `separator` (default `.`) is the decimal separator. `comma=""` turns off the thousands separator, for example for a year.

```blade
<x-input.number label="Year" wire:model="form.year" decimals="0" comma="" />
<x-input.number label="Price" wire:model="form.price" comma=" " separator="," suffix="EUR" />
```

The separators change only what the input shows. The property always gets a `.` before the decimals and no thousands separator.

## Negative Numbers

The input accepts a minus sign. `disable-negatives` removes it. Phones then show a number keypad.

```blade
<x-input.number label="Weight" wire:model="form.weight" suffix="kg" disable-negatives />
```

## Live Updates

`wire:model` sits on a hidden input that holds the plain number. Each event of the shown input is repeated on the hidden input, so every modifier works. For example, `.live` sends the number while the user types, and `.blur` sends it when the user leaves the field.

```blade
<x-input.number label="Reading" wire:model.live="form.reading" suffix="mm" />
```

## Default Value

The checkbox shows the default with the separators and decimals of the field.

```blade
<x-input.number label="Home latitude" wire:model="form.home_latitude" decimals="5" default="3.1390030" />
```

## What the Property Gets

- A string such as `"1234.50"`. An empty input gives `""`. Validate it with the `numeric` rule.
- If the property has more decimals than `decimals`, the input shows it rounded. The property keeps its value until the user edits the input.
- `min` and `max` have no effect, because the input is a text field. Validate the range on the server.

## Reference

### Props

| Prop                | Default | Description                                                                           |
|---------------------|---------|---------------------------------------------------------------------------------------|
| Field props         |         | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `decimals`          | `2`     | The number of decimals.                                                               |
| `comma`             | `,`     | The thousands separator. `""` turns it off.                                           |
| `separator`         | `.`     | The decimal separator.                                                                |
| `disable-negatives` | `false` | Removes the minus sign.                                                               |
| `prefix`            |         | Text in a box before the input.                                                       |
| `suffix`            |         | Text in a box after the input.                                                        |
| `size`              |         | `sm` (32 px) or `xs` (24 px). Without it, the input is 40 px high.                    |
| `default`           |         | Adds a checkbox below the input that fills in this number.                            |
| `default-label`     | "Use the default: *number*" | The label of the default checkbox.                                |
| `input-class`       |         | Classes for the wrapper of the input.                                                 |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |
| `prefix`    | Your own element before the input. It is not in a box.                         |
| `suffix`    | Your own element after the input. It is not in a box.                          |

### Attributes

`wire:model` with any modifier, `name`, and `value` go to the hidden input. `class` goes to the field. All other attributes go to the shown input, such as `id`, `placeholder`, `disabled`, and `readonly`.
