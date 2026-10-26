---
paths:
  - 'app/Livewire/**'
---

# Livewire

## Use short translation keys
Translate UI text with short keys from PHP files under `lang/en/`, such as `__('user/settings.profile.updated')`, never JSON sentence keys. Put shared labels in `lang/en/common/` (`buttons.php`, `inputs.php`).

## Never use em dashes
Never write an em dash (`—` or `&mdash;`) in the frontend. Rewrite the sentence, or use a comma, colon, period, or parentheses instead.

## Cut secondary text that adds no information
Add a description, hint, or secondary label only when it tells the user something the heading and controls do not. Examples are a reason, a consequence, a next step, or what a feature does. Cut text that only repeats the heading. If a screen looks bare without it, fix the layout. If you are not sure, ask the user.

## Type dates as Carbon in Livewire forms
Type date and date-time properties of Livewire forms and components as `?CarbonImmutable`, the date class of the app, not as `string`. Use `Flux\DateRange` and `Flux\DateTimeRange` for ranges. Keep a time of day as an `H:i` string, because Carbon always holds a date.

Keep the values in the app time zone, `app.timezone`. `<x-input.datetime>` shows and edits them in the time zone of the user, so do not convert them yourself.
