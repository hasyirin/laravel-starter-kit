---
paths:
  - 'app/**'
---

# App

## Organize code by domain under app/Domain
Put domain code in `app/Domain/<Context>/<Kind>/`, and shared contexts in `app/Domain/Shared/<Context>/`. Use one folder per kind, such as `Models`, `Factories`, `Seeders`, `Actions`, `Observers`, `Policies`, `Events`, `Jobs`, `Listeners`, and `Commands`. Write factories and seeders by hand, because `make:factory` and `make:seeder` write to `database/`. Event discovery (`withEvents()` in `bootstrap/app.php`) scans every `Listeners` folder under `app/Domain`, at any depth, so do not register domain listeners by hand. In the same way, `bootstrap/app.php` registers the Artisan commands in every `Commands` folder under `app/Domain` when the console kernel starts. So do not register domain commands by hand.

## Use helpers instead of facades
Call the `auth()`, `config()`, `cache()`, `context()`, and `logger()` helpers instead of their facades. Use a facade only when no helper covers the call, such as `Gate::before()` or `Log::spy()`.

## Get classes where you use them
Get an Action class or another class in the code that uses it, not through a parameter of a method or a constructor. Get an Action class with `UpdateUserInformation::make()` and any other class with `resolve(EnableTwoFactorAuthentication::class)`. Use `request()` for the request. `make()` and `resolve()` use the container, so a test can still swap the class. Keep the parameters that Laravel fills with data. Examples are a route-bound model, a listener's event, a policy's arguments, and a middleware's `$request` and `$next`.

## Use str() instead of Str::
Use the fluent `str()` helper whenever there is a string to transform, for example `str($name)->initials()->value()`. Use static `Str::` only for calls without an input string, such as `Str::uuid7()` or `Str::random()`.

## Check permissions, never roles
Authorize and guard with permissions only, such as `$user->can()` or `checkPermissionTo()`. Never check a role, such as `hasRole('system-admin')`, and never compare role names. Staff change roles at runtime on the roles pages, and a project can rename them. If no permission fits, add one to `config/system.php`. The `system-admin` role is only a seeded role that holds `bypass-authorization`. `Gate::before()` checks that permission, not the role.

## Update the docs with the code
After you change code that a doc in `docs/` describes, run `php artisan docs:generate` and update the hand-written text of that doc. `composer test` fails when a generated section is out of date or a doc names code that no longer exists.
