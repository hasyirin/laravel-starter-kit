# Languages

The app shows its text in English or in Malay. Each user picks a language, and a guest gets the language of their browser. `system.languages` in `config/system.php` lists the languages, each with its own name: `en` is English and `ms` is Bahasa Melayu.

The language is not the locale. The language sets the words. The locale (`users.locale`, `system.locales`) sets the format of dates and times. A user can read Malay text with English dates. See [kit ADR-0016](adr/kit/0016-language-is-separate-from-locale.md).

## How the App Chooses the Language

`App\Http\Middleware\ResolveUserPreferences` sets the language of each web request. It takes the first of these that the app lists:

1. The language of the user, `users.language`.
2. The choice from the language switch, which the app keeps in the `language` cookie.
3. The first language of the browser.
4. The default, `system.language`. It takes its value from `APP_LOCALE`.

The app never uses a language that `system.languages` does not list. When the default is not in the list, the first listed language is the default.

A user without a language gets one on their first signed-in request, from step 2 or step 3. The app saves it without an audit entry. During impersonation, the app does not fill it, so the browser of the impersonator never sets the preferences of another user. The language switch also does not show during impersonation. A user who registers gets the language of the registration page.

## Where a User Changes the Language

- **The language switch**: `App\Livewire\Components\LanguageSwitch`. The auth pages show it under the form, with each language in its own name. The headers of the app, internal, and system areas show the short form before the notifications icon, for example EN and BM. `system.language_abbreviations` sets the short names, and a language without one shows its code in capitals.
- **The preferences page**: the Language field, next to the time zone and the date and time format.

Both save the language of a signed-in user with an audit entry, and both keep it in the `language` cookie. So the login page shows the same language after the user logs out. Then the page loads again in the new language.

## Messages

An email, an SMS, or a notification uses the language of its recipient, not of the user who caused it. `User` implements `Illuminate\Contracts\Translation\HasLocalePreference`, so Laravel does this for every notification, also on the queue. A recipient without a listed language gets the default.

## Letters

A letter does not follow the language of the user who prints it. Its letter type sets the locale, and the language part of that locale sets the language of the letter. See [Letters](modules/letters.md).

## Add Text

Put each text in a PHP file under `lang/en`, with a short key, and add the same key to the file of every other language, such as `lang/ms`. `tests/Feature/Lang/ParityTest.php` fails when:

- a key is in one language only
- a translation has other placeholders than the English text, such as `:name`
- a translation is empty

`lang/ms.json` holds the Malay for the English sentences that packages use as keys: Flux, Fortify, Livewire, Laravel, and its passkeys package. Filament has its own Malay text. Do not put the text of the app in this file.

## Name the Fields in Validation Messages

A validation message names its field, for example "The department field is required". The names are in `attributes` of `lang/en/validation.php` and of the same file of every other language. Add each field that a form validates, with its key, such as `department_id`. The names reach every validator, also the Action classes of Fortify.

A form gives its own name only for a key that means another thing in another form, such as `type`. A Livewire form does this in `validationAttributes()`. The audit log also uses these names as labels for the fields of a change.

## Write Malay

Use the `_Malay_` term of `GLOSSARY.md` for each glossary term. `.ai/rules/lang.md` gives the style: formal Malaysian usage, with common IT terms in English.

## Add a Language

1. Add the code and the own name of the language to `system.languages`. Add a short name to `system.language_abbreviations` if the code in capitals does not fit.
2. Copy `lang/en` to a folder with the code as its name, and translate each file.
3. Add a JSON file with the code as its name for the package sentences, like `lang/ms.json`.
4. Run the tests. `tests/Feature/Lang/ParityTest.php` checks every listed language.

## Use One Language

List one language in `system.languages`. The app then always uses it. The language switch and the Language field do not show.
