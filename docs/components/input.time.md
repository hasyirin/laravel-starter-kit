# Time

`<x-input.time>` is a time field. The user types the time or picks it from a list. It wraps `flux:time-picker`.

```blade
<x-input.time label="Opening time" wire:model="form.opening_time" />
```

The property gets the time as `H:i` on a 24-hour clock, for example `14:30`, also when the input shows 2:30 pm. It takes every [field prop](field.md).

## Defaults

The kit changes these Flux defaults:

| Prop     | Kit default                            | Flux default       |
|----------|----------------------------------------|--------------------|
| `type`   | `input`                                | `button`           |
| `locale` | `system.locale` in `config/system.php` | The browser locale |

With `en-MY`, the input shows a 12-hour clock with am and pm. `time-format="24-hour"` changes that.

## Button

`type="button"` shows the time as text on a button. The user picks the time from the list only.

```blade
<x-input.time label="Pickup time" wire:model="form.pickup_time" type="button" />
```

## Interval and Limits

`interval` sets the minutes between the times in the list. The default is 30. `min` and `max` limit the times, and `unavailable` blocks single times. Validate the time on the server too.

```blade
<x-input.time label="Appointment time" wire:model="form.appointment_time" interval="15" min="09:00" max="17:00" unavailable="12:00,12:15,12:30,12:45" />
```

## Time Format

```blade
<x-input.time label="Shift starts" wire:model="form.shift_starts" time-format="24-hour" />
```

## Reference

### Props

| Prop          | Default         | Description                                          |
|---------------|-----------------|------------------------------------------------------|
| Field props   |                 | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `type`        | `input`         | `input` for a typed time, `button` for a button.     |
| `locale`      | `system.locale` | The locale of the time format.                       |
| `input-class` |                 | Classes for the picker.                              |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |

### Attributes

All other attributes go to `flux:time-picker`, so its props work: `interval`, `min`, `max`, `unavailable`, `time-format`, `multiple`, `open-to`, `dropdown`, `clearable`, `placeholder`, `size`, `disabled`, and `invalid`. `class` goes to the field.
