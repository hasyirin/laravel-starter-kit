---
paths:
  - 'resources/views/**'
---

# Views

## Use short translation keys
Translate UI text with short keys from PHP files under `lang/en/`, such as `__('user/settings.profile.updated')`, never JSON sentence keys. Put shared labels in `lang/en/common/` (`buttons.php`, `inputs.php`).

## Never use em dashes
Never write an em dash (`—` or `&mdash;`) in the frontend. Rewrite the sentence, or use a comma, colon, period, or parentheses instead.

## Cut secondary text that adds no information
Add a description, hint, or secondary label only when it tells the user something the heading and controls do not. Examples are a reason, a consequence, a next step, or what a feature does. Cut text that only repeats the heading. If a screen looks bare without it, fix the layout. If you are not sure, ask the user.

## Format dates and times for the user
Show a date or a time as text with `App\Domain\User\Support\Preferences::current()`: `date()`, `datetime()`, or `time()` for a moment, such as `created_at`. They use the time zone and the locale of the user. For a date-only value, such as a `date` cast or a birthday, use `calendarDate()`: it keeps the day as stored. Do not call `format()` on a Carbon in a view. In a mail to another user, use `Preferences::of($user)`.
