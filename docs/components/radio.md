# Radio

`<x-radio>` lets the user pick one option from a short list that stays in view. It wraps `flux:radio.group`.

```blade
<x-radio label="Carrier" wire:model="form.carrier" :options="['road' => 'Road', 'sea' => 'Sea', 'air' => 'Air']" />
```

It takes every [field prop](field.md). `options` takes the same forms as on [Select](select.md#options), and the slot takes Flux radios, after the options of `options`. For a long list, use [`<x-select>`](select.md).

## Variants

`variant` sets the look: `segmented`, `cards`, `buttons`, or `pills`. `segmented` shows the options in one bar, and `size="sm"` makes the bar smaller.

```blade
<x-radio label="Carrier" wire:model="form.carrier_segmented" variant="segmented" :options="['road' => 'Road', 'sea' => 'Sea', 'air' => 'Air']" />
<x-radio label="Carrier" wire:model="form.carrier_small" variant="segmented" size="sm" :options="['road' => 'Road', 'sea' => 'Sea', 'air' => 'Air']" />
```

Cards show the description and the icon of an option. The default variant shows the description too.

```blade
@use(App\Livewire\Components\Option)

<x-radio label="Plan" wire:model="form.plan_card" variant="cards" :options="[
    Option::make('monthly')->label('Monthly')->icon('calendar')->description('Pay each month'),
    Option::make('yearly')->label('Yearly')->icon('calendar-days')->description('Two months free'),
]" />
```

## Reference

### Props

| Prop          | Default   | Description                                                                                                                 |
|---------------|-----------|-----------------------------------------------------------------------------------------------------------------------------|
| Field props   |           | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `options`     | `[]`      | The options. See [Select](select.md#options).                                                                               |
| `variant`     | `default` | `default`, `segmented`, `cards`, `buttons`, or `pills`.                                                                     |
| `input-class` |           | Classes for the group.                                                                                                      |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |
| `default`   | Flux radios, after the options of `options`.                                   |

### Attributes

All other attributes go to `flux:radio.group`. These include `wire:model` with any modifier, `name`, `size`, `disabled`, and `invalid`. `class` goes to the field.
