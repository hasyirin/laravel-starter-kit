# Date

`<x-input.date>` is a date field. The user types the date or picks it from a calendar. It wraps `flux:date-picker`.

```blade
<x-input.date label="Start date" wire:model="form.start_date" />
```

The property gets the date as `Y-m-d`, for example `2026-10-26`. Type it as `?CarbonImmutable`. A date has no time zone, so the picker never converts it. It takes every [field prop](field.md).

## Defaults

The kit changes these Flux defaults:

| Prop                | Kit default                                  | Flux default        |
|---------------------|----------------------------------------------|---------------------|
| `type`              | `input`, or `button` with `mode="range"` | `button`           |
| `with-today`        | `true`                                   | `false`            |
| `selectable-header` | `true`                                   | `false`            |
| `locale`            | The locale of the user                   | The browser locale |

Each user picks a locale in the profile settings, and a new user gets the one of their browser. The kit default, `system.locale` in `config/system.php`, is `en-MY`: English month names, with the day before the month. Turn a default off with `:with-today="false"` or `:selectable-header="false"`.

## Button

`type="button"` shows the date as text on a button. The user picks the date from the calendar only.

```blade
<x-input.date label="Delivery date" wire:model="form.delivery_date" type="button" />
```

## Range

`mode="range"` picks a start and an end date. The property gets an array with `start` and `end`, or use a `Flux\DateRange` property. A range opens from the button, because the typed input holds one date.

```blade
<x-input.date label="Leave" wire:model="form.leave" mode="range" />
```

## Limits

`min` and `max` take a date or `today`. `unavailable` takes a list of dates that the user cannot pick. These limits apply in the browser only, so validate the date on the server too.

```blade
<x-input.date label="Appointment" wire:model="form.appointment" min="today" unavailable="2026-12-25,2027-01-01" />
```

## Clearable

```blade
<x-input.date label="Due date" wire:model="form.due_date" clearable />
```

## Custom Trigger

The `trigger` slot replaces the input with your own button.

```blade
<x-input.date label="Review date" wire:model="form.review_date">
    <x-slot:trigger>
        <flux:button icon="calendar">Pick a date</flux:button>
    </x-slot:trigger>
</x-input.date>
```

## Reference

### Props

| Prop                | Default                                  | Description                                                           |
|---------------------|------------------------------------------|-----------------------------------------------------------------------|
| Field props         |                                          | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `type`              | `input`, or `button` with `mode="range"` | `input` for a typed date, `button` for a button.                      |
| `with-today`        | `true`                                   | Shows a button that moves the calendar to today.                      |
| `selectable-header` | `true`                                   | Shows menus for the month and the year.                               |
| `locale`            | The locale of the user                   | The locale of the month names and the date order.                     |
| `input-class`       |                                          | Classes for the picker.                                               |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |
| `trigger`   | Your own element that opens the calendar, such as a button.                    |

### Attributes

`wire:model` goes to `flux:date-picker`. Flux keeps the focus on an element inside the picker, so the kit sends the blur event when the focus leaves the picker. Every modifier works, also `.blur` and `.live.blur`.

All other attributes go to `flux:date-picker`, so its props work: `mode`, `min`, `max`, `unavailable`, `months`, `min-range`, `max-range`, `with-presets`, `presets`, `with-inputs`, `with-confirmation`, `week-numbers`, `start-day`, `open-to`, `force-open-to`, `clearable`, `placeholder`, `size`, `disabled`, and `invalid`. `class` goes to the field.
