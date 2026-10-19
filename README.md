# Laravel Template

A personal starting point for new Laravel projects. It is based on the official Laravel Livewire starter kit, with extra packages and different environment defaults.

## Stack

- PHP 8.5+, Laravel 13
- Livewire 4, Flux and Flux Pro, Blaze
- Filament 5 components: actions, forms, infolists, notifications, schemas, tables, and widgets
- Fortify for authentication, with passkey support
- Spatie Activitylog to log model and user activity
- Spatie Media Library to attach files to Eloquent models
- Spatie Permission for roles and permissions
- PostgreSQL in production, SQLite for local work and tests
- Tailwind CSS 4, Vite (via Vite+)
- Pest 5 with test impact analysis
- PHPStan (Larastan) at level 10, Rector, Pint, the Vite+ linter and formatter, and Prettier for Blade
- Debugbar and Ray for local debugging
- Laravel Boost for AI-assisted development

## Differences From the Starter Kit

- **PHP 8.5** is the minimum version, instead of 8.3. The `bcmath` extension is required.
- **Flux Pro** is installed from `composer.fluxui.dev`. You need a Flux Pro license.
- **Filament 5 components** are installed. `resources/css/app.css` imports their styles, and `php artisan filament:upgrade` runs after each Composer autoload dump.
- **Fonts** are Figtree and JetBrains Mono. Vite downloads them from Bunny Fonts at build time. The accent color is blue.
- **Debugbar** and **Laravel Boost** are installed as dev dependencies.
- **Ray** is installed as a production dependency, so a `ray()` call left in the code does not break production. Ray is off in production.
- **Spatie Activitylog** is installed. Its migration creates the `activity_log` table.
- **Spatie Media Library** is installed. Its migration creates the `media` table. It stores files on a new public `media` disk (`storage/app/media`, served at `/media`).
- **Upload limit** is 1GB for the media library and for Livewire temporary uploads. PHP `upload_max_filesize` and `post_max_size` must also allow this size.
- **PostgreSQL** is the production database. Local work and tests use SQLite. The migrations need PostgreSQL or SQLite, because the users table uses a partial unique index, which MySQL and MariaDB do not support.
- **Users are soft deleted.** Deleting an account keeps the row. A deleted user cannot log in, and their email is free to register again: the unique index on `users.email` covers only users that are not deleted.
- **Roles and permissions** use Spatie Permission. Permissions are defined in `config/system.php`. `PermissionSeeder` creates them and deletes the ones that are no longer in the config. `RoleSeeder` only adds permissions to roles, so changes made in production stay. A user with the `bypass-authorization` permission passes every Gate check. System roles (`is_system`) are created by the seeders and cannot be renamed or deleted.
- **Policies** extend `App\Domain\Shared\Common\Policies\Policy`. It builds permission names from the policy's `$prefix` and class name. For example, `RolePolicy` has the prefix `admin.controls`, so it checks `admin.controls.roles.<action>`.
- **Redis** is the default driver for session, cache, and queue.
- **Mail intercept** sends every email to the current user when `MAIL_INTERCEPT=true`. Use it on local or staging copies with real data, so no email reaches a real recipient. If there is no current user, the email goes to `MAIL_INTERCEPT_ADDRESS`, or it is dropped when that is empty. Queued emails go to the user who was logged in when they were queued. The intercept is always off in production.
- **Context** carries `trace_id` and `user_id` into every log line and queued job. Each request gets a trace ID, or reuses a valid incoming `X-Request-Id`, and the response returns it in `X-Request-Id`. Activity logged in queued jobs uses the `user_id` from Context as its causer.
- **Daily log files** replace the single log file (`LOG_STACK=daily`).
- **`APP_DOMAIN`** is a new setting. It is available as `config('app.domain')`, and `APP_URL` is built from it.
- **Livewire config** is published to `config/livewire.php`.
- **HTTPS** is forced for all generated URLs, in every environment. Serve the app over HTTPS locally too.
- **Models, factories, seeders, concerns, and actions** live in domain folders. A model's factory sits in the sibling `Factories` folder, for example `App\Domain\User\Factories\UserFactory`. The model links to its factory in `newFactory()`, and the factory links back with `$model`. Seeders extend `App\Domain\Shared\Common\Seeders\Seeder`. Fortify actions have their own `Fortify` folder only to keep them together, for example `App\Domain\User\Actions\Fortify\CreateNewUser`. Create a model with `php artisan make:model Domain/Billing/Models/Invoice`. Write its factory and seeder by hand, because `make:factory` and `make:seeder` still write to `database/`.
- **The base model**, `App\Domain\Shared\Common\Models\Model`, adds soft deletes and activity logging. A model that extends it needs a `deleted_at` column and an entry in the morph map. The activity log stores the model as a polymorphic subject, so without that entry, creating, updating, deleting, or restoring it throws `ClassMorphViolationException`. The log records changes to fillable attributes, but not to attributes in `$hidden`. Keep secrets in `$hidden`.
- **Strict models** are on outside production. Lazy loading, reading a missing attribute, or mass-assigning an attribute that is not fillable throws an exception.
- **Morph map** is enforced, and `user` maps to `App\Domain\User\Models\User`. Add each new polymorphic model to the map in `AppServiceProvider`.
- **Blaze** optimizes the Blade components in `resources/views/components`.
- **Tests** use Pest syntax and run with test impact analysis.
- **Code checks** are stricter. PHPStan runs at level 10 with extra Larastan checks and Livewire support. Rector runs with Laravel and Pest rules. Vite+ lints the JavaScript and formats the JavaScript and CSS in `resources/`. Prettier formats the Blade views and sorts their Tailwind classes.

## Requirements

- PHP 8.5+ with the `phpredis`, `exif`, `gd`, `bcmath`, and `pdo_pgsql` extensions
- PostgreSQL in production
- Composer 2
- Node.js 22.18+ or 24.11+
- Bun
- Redis
- A Flux Pro license
- Xdebug (optional), for test impact analysis

## Setup

1. Add your Flux Pro credentials. This creates `auth.json`, which git ignores:

   ```bash
   composer config http-basic.composer.fluxui.dev "<your-email>" "<your-license-key>"
   ```

2. Install dependencies, create `.env`, generate the app key, run migrations, create the storage links, and build assets:

   ```bash
   composer setup
   ```

3. Make sure Redis runs on `127.0.0.1:6379`, or change the `REDIS_*` values in `.env`.

4. If you serve the app from a different host, set `APP_DOMAIN` in `.env`.

5. If you use an AI coding agent, generate the Boost guidelines, skills, and MCP config. git ignores the generated files. After this, `composer update` also runs `php artisan boost:update` to keep them current:

   ```bash
   php artisan boost:install
   ```

6. Install the image optimizers. The media library uses them to make converted images smaller. On Debian or Ubuntu:

   ```bash
   sudo apt install jpegoptim optipng pngquant gifsicle libavif-bin webp
   bun add -g svgo
   ```

## Development

```bash
composer dev
```

## Quality Checks

| Command                | Description                                                                         |
|------------------------|-------------------------------------------------------------------------------------|
| `composer test`        | Clear the config cache, run `lint:check` and `types:check`, then run the Pest tests |
| `composer lint`        | Fix code with Rector, Pint, the Vite+ linter and formatter, and Prettier            |
| `composer lint:check`  | Check Pint, Rector, Vite+ linting and formatting, and Prettier without changes      |
| `composer types:check` | Run PHPStan (Larastan) at level 10                                                  |
| `composer ci:check`    | Run `composer test` with no process timeout, as CI does                             |

`composer test` runs the tests with test impact analysis (`--tia`). With Xdebug installed, Pest runs only the tests that your changes affect. Without it, Pest runs all tests.

`composer lint` also removes `->only()` from tests, and `dump`, `dd`, and `ray` calls from `expect()` chains.

## Continuous Integration

`.github/workflows/tests.yml` runs `composer ci:check` on pushes to `main` and on pull requests. It caches the Rector and PHPStan results.

CI must install Flux Pro, so add your Flux credentials as repository secrets. Add them as Dependabot secrets too, so that Dependabot pull requests can also install Flux Pro. Each command asks for the value:

```bash
gh secret set FLUX_USERNAME
gh secret set FLUX_LICENSE_KEY
gh secret set FLUX_USERNAME --app dependabot
gh secret set FLUX_LICENSE_KEY --app dependabot
```

Pull requests from forks do not get these secrets, so their CI fails when Composer installs Flux Pro.

## License

MIT
