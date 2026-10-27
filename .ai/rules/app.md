---
paths:
  - 'app/**'
---

# App

## Organize code by domain under app/Domain
Put domain code in `app/Domain/<Context>/<Kind>/`, and shared contexts in `app/Domain/Shared/<Context>/`. Use one folder per kind, such as `Models`, `Factories`, `Seeders`, `Actions`, `Observers`, `Policies`, `Events`, `Jobs`, `Listeners`, and `Commands`. Write factories and seeders by hand, because `make:factory` and `make:seeder` write to `database/`. Event discovery (`withEvents()` in `bootstrap/app.php`) scans every `Listeners` folder under `app/Domain`, at any depth, so do not register domain listeners by hand. In the same way, `bootstrap/app.php` registers the Artisan commands in every `Commands` folder under `app/Domain` when the console kernel starts, so do not register domain commands by hand.

## Use helpers instead of facades
Call the `auth()`, `config()`, `cache()`, `context()`, and `logger()` helpers instead of their facades. Use a facade only when no helper covers the call, such as `Gate::before()` or `Log::spy()`.

## Use str() instead of Str::
Use the fluent `str()` helper whenever there is a string to transform, for example `str($name)->initials()->value()`. Use static `Str::` only for calls without an input string, such as `Str::uuid7()` or `Str::random()`.

## Update the docs with the code
After you change code that a doc in `docs/` describes, run `php artisan docs:generate` and update the hand-written text of that doc. `composer test` fails when a generated section is out of date or a doc names code that no longer exists.
