---
paths:
  - 'app/**'
---

# App

## Organize code by domain under app/Domain
Put domain code in `app/Domain/<Context>/<Kind>/`, and shared contexts in `app/Domain/Shared/<Context>/`. Use one folder per kind, such as `Models`, `Factories`, `Seeders`, `Observers`, `Policies`, `Events`, `Jobs`, and `Listeners`. Write factories and seeders by hand, because `make:factory` and `make:seeder` write to `database/`. Event discovery (`withEvents()` in `bootstrap/app.php`) scans every `Listeners` folder under `app/Domain`, at any depth, so do not register domain listeners by hand.

## Use helpers instead of facades
Call the `auth()`, `config()`, `cache()`, `context()`, and `logger()` helpers instead of their facades. Use a facade only when no helper covers the call, such as `Gate::before()` or `Log::spy()`.

## Use str() instead of Str::
Use the fluent `str()` helper whenever there is a string to transform, for example `str($name)->initials()->value()`. Use static `Str::` only for calls without an input string, such as `Str::uuid7()` or `Str::random()`.
