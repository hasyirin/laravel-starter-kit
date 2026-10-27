# Checkbox

`<x-checkbox>` is one checkbox, or a group of checkboxes when it has options. It wraps `flux:checkbox` and `flux:checkbox.group`.

```blade
<x-checkbox label="Send me the newsletter" wire:model="form.newsletter" />
```

## One Checkbox

Without `options` and without a slot, it is one checkbox with its label beside it. The property gets `true` or `false`. It takes `label`, `description`, `required`, `badge`, and `no-error-message`. The asterisk sits outside the label, as on every kit input.

```blade
<x-checkbox label="I accept the terms" description="We keep a copy of your answer." wire:model="form.accept_terms" required />
```

## Group

With `options`, it is a group under a label row, like the other kit inputs, and it takes every [field prop](field.md). The property gets an array of the ticked values. `options` takes the same forms as on [Select](select.md#options). An empty list still makes a group, so a list that has no options yet keeps its shape.

```blade
<x-checkbox label="Work days" wire:model="form.work_days" :options="['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday']" />
```

The field shows the first error of the property and of its items, such as work_days.0. The slot takes Flux checkboxes, after the options of `options`. A slot with checkboxes makes a group too.

## Select All

`all` adds a checkbox before the options that ticks all of them. It shows "Select all", or the text that you give to `all`.

```blade
<x-checkbox label="Delivery days" wire:model="form.delivery_days" :options="['sat' => 'Saturday', 'sun' => 'Sunday']" all />
<x-checkbox label="Opening days" wire:model="form.opening_days" :options="['sat' => 'Saturday', 'sun' => 'Sunday']" all="Every weekend day" />
```

## Variants

`variant` sets the look of a group: `cards`, `buttons`, or `pills`. Cards show the description and the icon of an option, and buttons show the icon.

```blade
@use(App\Livewire\Components\Option)

<x-checkbox label="Add-ons" wire:model="form.add_ons" variant="cards" :options="[
    Option::make('backup')->label('Backups')->icon('cloud')->description('Every night, kept for 30 days'),
    Option::make('support')->label('Support')->icon('lifebuoy')->description('Replies within one working day'),
]" />
<x-checkbox label="Toppings" wire:model="form.toppings" variant="pills" :options="['cheese' => 'Cheese', 'egg' => 'Egg', 'onion' => 'Onion']" />
```

## Reference

### Props

| Prop          | Default   | Description                                                                                                                                                                                                  |
|---------------|-----------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Field props   |           | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). One checkbox takes `label`, `required`, `badge`, `description`, and `no-error-message` only. |
| `options`     |           | The options of a group. See [Select](select.md#options). Without it, and without a slot, the component is one checkbox.                                                                                      |
| `variant`     | `default` | The look of a group: `default`, `cards`, `buttons`, or `pills`.                                                                                                                                              |
| `all`         |           | Adds a checkbox that ticks all options. Give it a text to change its label.                                                                                                                                  |
| `input-class` |           | Classes for the checkbox or the group.                                                                                                                                                                       |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |
| `default`   | Flux checkboxes of a group, after the options of `options`.                    |

### Attributes

All other attributes go to the checkbox or the group. These include `wire:model` with any modifier, `name`, `value`, `checked`, `disabled`, and `invalid`. `class` goes to the field.
