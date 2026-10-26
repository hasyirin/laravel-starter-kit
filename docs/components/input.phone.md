# Phone

`<x-input.phone>` is a phone number field with a country menu. It wraps `flux:phone`.

```blade
<x-input.phone label="Phone" wire:model="form.phone" />
```

The input shows the number in the format of its country, for example `012-345 6789`. The property gets the international E.164 format, for example `+60123456789`. It takes every [field prop](field.md).

## Defaults

The kit changes this Flux default:

| Prop      | Kit default                                    | Flux default |
|-----------|------------------------------------------------|--------------|
| `country` | `system.country` in `config/system.php`, `MY`  | No country   |

## Country

The country starts at `system.country` in `config/system.php`, which is `MY`. `country` sets another one. When the user types a number with a country code, such as `+65`, the menu changes to that country.

```blade
<x-input.phone label="Phone in Singapore" wire:model="form.singapore_phone" country="SG" />
```

## Country List

`countries` limits the menu, and `country-order` puts countries at the top. Neither limits the numbers that the user can type.

```blade
<x-input.phone label="Office phone" wire:model="form.office_phone" :countries="['MY', 'SG', 'BN', 'ID']" :country-order="['MY', 'SG']" />
```

## Sizes

```blade
<x-input.phone label="Small" wire:model="form.phone_small" size="sm" />
<x-input.phone label="Extra small" wire:model="form.phone_tiny" size="xs" />
```

## Validation

The input formats the number but does not check it. A number that it cannot read, or an incomplete one, stays as the user typed it. So validate the number on the server, for example with `regex:/^\+[1-9]\d{6,14}$/`.

## Reference

### Props

| Prop          | Default          | Description                                                     |
|---------------|------------------|-----------------------------------------------------------------|
| Field props   |                  | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `country`     | `system.country` | The country of a number without a country code, such as `SG`.   |
| `input-class` |                  | Classes for the phone input.                                    |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |

### Attributes

All other attributes go to `flux:phone`, so its props work: `countries`, `country-order`, `placeholder`, `size`, `variant`, `disabled`, `readonly`, and `invalid`. `class` goes to the field.
