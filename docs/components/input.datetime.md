# Date and Time

`<x-input.datetime>` picks a date and a time in one field. It wraps `flux:datetime-picker`.

```blade
<x-input.datetime label="Starts at" wire:model="form.starts_at" />
```

The property gets `Y-m-d\TH:i`, for example `2026-10-26T14:30`, the same format as a `datetime-local` input. A `Carbon` property works too. It takes every [field prop](field.md).

## Defaults

The kit changes these Flux defaults:

| Prop                | Kit default                            | Flux default       |
|---------------------|----------------------------------------|--------------------|
| `with-today`        | `true`                                 | `false`            |
| `selectable-header` | `true`                                 | `false`            |
| `locale`            | `system.locale` in `config/system.php` | The browser locale |

## Time Zones

The value is a wall-clock time. It has no time zone, so `2026-10-26T14:30` means 2:30 pm on 26 October wherever the user is. A `Carbon` property reads it in the time zone of the app. If your users are in other time zones, convert the value yourself, for example with `Carbon::parse($value, $timezone)->utc()`.

## Typed Time

By default, the user picks a time from a list. `time="input"` lets the user type the exact minute. The user then confirms with the Select button.

```blade
<x-input.datetime label="Arrived at" wire:model="form.arrived_at" time="input" />
```

## Interval and Limits

`interval` sets the minutes between the times in the list. `min` and `max` take a date and time, a date, `now`, or `today`. `min-time` and `max-time` limit the time on every day. Validate the value on the server too.

```blade
<x-input.datetime label="Meeting" wire:model="form.meeting" interval="15" min="now" min-time="09:00" max-time="17:00" />
```

## Default Time

`default-time` fills in a time when the user picks a date.

```blade
<x-input.datetime label="Reminder" wire:model="form.reminder" default-time="09:00" />
```

## Range

`mode="range"` picks a start and an end, each with its own time. The property gets an array with `start` and `end`, or use a `Flux\DateTimeRange` property.

```blade
<x-input.datetime label="Maintenance window" wire:model="form.maintenance_window" mode="range" />
```

## Custom Trigger

The `trigger` slot replaces the button with your own button.

```blade
<x-input.datetime label="Send at" wire:model="form.send_at">
    <x-slot:trigger>
        <flux:button icon="calendar-days">Schedule</flux:button>
    </x-slot:trigger>
</x-input.datetime>
```

## Reference

### Props

| Prop                | Default         | Description                                       |
|---------------------|-----------------|---------------------------------------------------|
| Field props         |                 | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `with-today`        | `true`          | Shows a button that moves the calendar to today.  |
| `selectable-header` | `true`          | Shows menus for the month and the year.           |
| `locale`            | `system.locale` | The locale of the month names and the time format. |
| `input-class`       |                 | Classes for the picker.                           |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |
| `trigger`   | Your own element that opens the picker, such as a button.                      |

### Attributes

All other attributes go to `flux:datetime-picker`, so its props work: `mode`, `time`, `interval`, `time-format`, `min`, `max`, `min-time`, `max-time`, `unavailable`, `default-time`, `min-range`, `max-range`, `months`, `with-confirmation`, `week-numbers`, `start-day`, `fixed-weeks`, `open-to`, `clearable`, `placeholder`, `size`, `disabled`, and `invalid`. `class` goes to the field.
