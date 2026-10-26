# Time

`<x-input.time>` is a time field. The user types the time or picks it from a list. It wraps `flux:time-picker`.

```blade
<x-input.time label="Opening time" wire:model="form.opening_time" />
```

The property gets the time as `H:i` on a 24-hour clock, for example `14:30`, also when the input shows 2:30 pm. Keep the property a string, because Carbon always holds a date. A time of day has no time zone, so the picker never converts it. It takes every [field prop](field.md).

## Defaults

The kit changes these Flux defaults:

| Prop     | Kit default            | Flux default       |
|----------|------------------------|--------------------|
| `type`   | `input`                | `button`           |
| `locale` | The locale of the user | The browser locale |

The clock follows the locale: `en-MY` and `ms-MY` show 12 hours, and `en-GB` shows 24 hours. `time-format` sets the clock for one field.

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

| Prop          | Default                | Description                                          |
|---------------|------------------------|------------------------------------------------------|
| Field props   |                 | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `type`        | `input`                | `input` for a typed time, `button` for a button.     |
| `locale`      | The locale of the user | The locale of the time format.                       |
| `input-class` |                        | Classes for the picker.                              |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |

### Attributes

`wire:model` goes to `flux:time-picker`. Flux pickers do not send a blur event, so `.blur` and `.live.blur` never update the property. Use `wire:model.live` to send each change, or `wire:model` to send the value with the next request.

All other attributes go to `flux:time-picker`, so its props work: `interval`, `min`, `max`, `unavailable`, `time-format`, `multiple`, `open-to`, `dropdown`, `clearable`, `placeholder`, `size`, `disabled`, and `invalid`. `class` goes to the field.
