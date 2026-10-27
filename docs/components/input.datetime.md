# Date and Time

`<x-input.datetime>` picks a date and a time in one field. It wraps `flux:datetime-picker`.

```blade
<x-input.datetime label="Starts at" wire:model="form.starts_at" />
```

The picker shows the time in the time zone of the user, and the property holds the same moment in the app time zone, `app.timezone`. Type the property as `?CarbonImmutable`. It takes every [field prop](field.md).

## Defaults

The kit changes these Flux defaults:

| Prop                | Kit default            | Flux default       |
|---------------------|------------------------|--------------------|
| `time`              | `input`                | `list`             |
| `with-today`        | `true`                 | `false`            |
| `selectable-header` | `true`                 | `false`            |
| `locale`            | The locale of the user | The browser locale |

With `time="input"`, the user types the exact minute and then confirms with the Select button.

## Time Zones

- With the app time zone at its default, UTC, a user in Kuala Lumpur who picks 26 October, 2:30 pm, gives the property `2026-10-26 06:30`. With Asia/Kuala_Lumpur as the app time zone, the property gets `14:30`. When the property comes back from the server, the picker shows 2:30 pm again.
- So the property is a real moment: save it, compare it with `now()`, or validate it with `after:now` as usual.
- Keep the app time zone on one without daylight saving, such as UTC or Asia/Kuala_Lumpur. The property holds no offset, so in a zone with daylight saving, a time in the hour that repeats when the clocks go back is saved as the second one.
- Limits move to the time zone of the user too: a Carbon value, `now`, or `today` in `min`, `max`, or `open-to`, and Carbon values in `unavailable`. A string with a date or a time stays as it is, in the time zone of the user.
- Each user has a time zone in the profile settings. A new user gets the time zone of their browser.

## List of Times

`time="list"` lets the user pick a time from a list instead of typing it.

```blade
<x-input.datetime label="Arrived at" wire:model="form.arrived_at" time="list" />
```

## Interval and Limits

`interval` sets the minutes between the suggested times. `min` and `max` take a Carbon value, a date and time, a date, `now`, or `today`. `min-time` and `max-time` limit the time on every day. Validate the value on the server too.

```blade
<x-input.datetime label="Meeting" wire:model="form.meeting" interval="15" min="now" min-time="09:00" max-time="17:00" />
```

## Default Time

`default-time` fills in a time when the user picks a date.

```blade
<x-input.datetime label="Reminder" wire:model="form.reminder" default-time="09:00" />
```

## Range

`mode="range"` picks a start and an end, each with its own time. Type the property as `?Flux\DateTimeRange`. An array property gets `start` and `end` in the app time zone. Build a `Flux\DateTimeRange` in the app time zone too, for example from model dates or `now()`, because Flux sends its times without an offset.

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

| Prop                | Default                | Description                                                                 |
|---------------------|------------------------|-----------------------------------------------------------------------------|
| Field props         |                        | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `time`              | `input`                | `input` to type the time, `list` to pick it from a list.                   |
| `with-today`        | `true`                 | Shows a button that moves the calendar to today.                           |
| `selectable-header` | `true`                 | Shows menus for the month and the year.                                    |
| `locale`            | The locale of the user | The locale of the month names and the clock.                               |
| `min`               |                        | The earliest moment. A Carbon value, `now`, or `today` moves to the time zone of the user. |
| `max`               |                        | The latest moment, the same as `min`.                                      |
| `open-to`           |                        | The month and time that the picker opens to when it is empty.              |
| `unavailable`       |                        | Dates, times, and moments that the user cannot pick. Carbon values move to the time zone of the user. |
| `input-class`       |                        | Classes for the picker.                                                    |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |
| `trigger`   | Your own element that opens the picker, such as a button.                      |

### Attributes

`wire:model` with any modifier goes to a hidden element that holds the value in the app time zone. Flux keeps the focus on an element inside the picker, so the kit sends the blur event when the focus leaves the picker. Every modifier works, also `.blur` and `.live.blur`. Without `wire:model`, the picker works like `flux:datetime-picker`: `value` and the value that a plain form posts are in the time zone of the user. `class` goes to the field. All other attributes go to `flux:datetime-picker`, so its props work: `mode`, `interval`, `time-format`, `min-time`, `max-time`, `default-time`, `min-range`, `max-range`, `months`, `with-confirmation`, `week-numbers`, `start-day`, `fixed-weeks`, `clearable`, `placeholder`, `size`, `disabled`, and `invalid`.
