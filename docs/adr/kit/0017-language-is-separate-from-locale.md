# Language Is Separate from Locale

A user has a language (`users.language`) and a locale (`users.locale`). The language sets the words of the app, and `system.languages` lists the choices. The locale sets only the format of dates and times, as before. A change to one does not change the other. See [Languages](../../localization.md).

## Considered Options

- **One preference, with the language taken from the locale**: rejected. `ms-MY` would give Malay text and each English locale English text. It needs no new column, and letters already work this way. But a user could not read Malay text with English dates, or English text with Malay dates. Each user who had picked `ms-MY` for its dates would also get Malay text without asking for it.
- **Two preferences that follow each other**: rejected. If a change of language also changed the locale, the second preference would add a column and give nothing over one preference.

## Consequences

- A user with Malay text can see English month names, until they also change the date and time format. A new user gets both from the browser, so a Malay browser gives Malay text and Malay dates.
- Laravel has one locale for the words, and the app sets it to the language. Code that formats a date for a user must use `App\Domain\User\Support\Preferences`, which reads the locale of the user, not the locale of Laravel.
- Laravel writes the language of each request to `app.locale`. So the default language has its own config key, `system.language`, which no request changes.
- A letter still takes its language from the locale of its letter type, not from the user.
