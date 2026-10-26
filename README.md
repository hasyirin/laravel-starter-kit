# Laravel Template

A personal starting point for new Laravel projects. It is based on the official Laravel Livewire starter kit, with extra packages and different environment defaults.

## Stack

- PHP 8.5+, Laravel 13
- Livewire 4, Flux and Flux Pro, Blaze
- Filament 5 components: actions, forms, infolists, notifications, schemas, tables, and widgets
- Fortify for authentication, with passkey support
- Spatie Activitylog to log model and user activity
- Spatie Media Library to attach files to Eloquent models
- Spatie Model States for workflows
- Spatie Permission for roles and permissions
- Laravel Horizon for Redis queues
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
- **Debugbar** and **Laravel Boost** are installed as dev dependencies. The Boost guidelines, skills, and MCP config are committed, and `composer update` keeps the guidelines and skills current with `php artisan boost:update`.
- **Ray** is installed as a production dependency, so a `ray()` call left in the code does not break production. Ray is off in production.
- **Debug mode** is not allowed in production. If `APP_DEBUG=true` there, the app throws an exception at boot, so every request and Artisan command fails until you set `APP_DEBUG=false`. Requests get a plain 500 page, and the exception goes to the log.
- **Spatie Activitylog** is installed. Its migration creates the `activity_log` table.
- **Spatie Media Library** is installed. Its migration creates the `media` table. It stores files on a new public `media` disk (`storage/app/media`, served at `/media`).
- **Upload limit** is 1GB for the media library and for Livewire temporary uploads. PHP `upload_max_filesize` and `post_max_size` must also allow this size.
- **PostgreSQL** is the production database. Local work and tests use SQLite. The migrations need PostgreSQL or SQLite, because the users table uses a partial unique index, which MySQL and MariaDB do not support.
- **Users are soft deleted.** Deleting an account keeps the row. A deleted user cannot log in, and their email is free to register again: the unique index on `users.email` covers only users that are not deleted.
- **Roles and permissions** use Spatie Permission. Permissions are defined in `config/system.php`. `PermissionSeeder` creates them and deletes the ones that are no longer in the config. `RoleSeeder` only adds permissions to roles, so changes made in production stay. A user with the `bypass-authorization` permission passes every Gate check. System roles (`is_system`) are created by the seeders and cannot be renamed or deleted.
- **Profiles** (`App\Domain\User\Models\Profile`) record a user's organization, department, position, branch, roles, and permissions. A user can have many profiles, and the newest one is active. A user has the roles and permissions of their active profile. Profiles are snapshots: a profile cannot be updated or deleted, so to change any of these, create a new profile. `withEndedAt()` adds `ended_at`, the time when the user's next profile was created. The department, branch, and position must belong to the profile's organization, and the position must also belong to its department. Give a profile its roles and permissions before its first save, because they cannot change after that. A role that a profile has cannot be deleted.
- **Policies** extend `App\Domain\Shared\Common\Policies\Policy`. It builds permission names from the policy's `$prefix` and class name. For example, `RolePolicy` has the prefix `admin.controls`, so it checks `admin.controls.roles.<action>`.
- **Routes** are split into four areas: `guest.php` for everyone, `app.php` for signed-in users, `internal.php` for staff (`/internal`, needs `internal.access`), and `admin.php` for administration (`/admin`, needs `admin.access`). `routes/web.php` loads them. After login, a user goes to the page they asked for, or else to the highest area they can open. See [Routes and access areas](docs/routing.md).
- **Redis** is the default driver for session, cache, and queue. They share one Redis instance, so set its `maxmemory-policy` to `noeviction` or a `volatile-*` policy. Otherwise Redis can evict queued jobs when memory is full.
- **Horizon** runs the queues. The `supervisor-default` supervisor works the `default` queue with 1 to 10 processes and a 60-second timeout. It tries each job 3 times, 10 and 60 seconds apart, so a job that must not run twice sets `public $tries = 1`. Media library conversions go to their own `redis-media` connection (`MEDIA_QUEUE_CONNECTION`), where the `supervisor-media` supervisor runs 2 processes with a 600-second timeout, 512 MB each, and a lower CPU priority (`nice` 10). Workers restart every hour, and default workers also restart after 1,000 jobs. Environments without their own entry, such as staging, use the `*` entry. Keep each supervisor's `timeout` below its connection's `retry_after`, or a job can run twice. A test checks this. The dashboard at `/admin/horizon` needs the `admin.horizon.view` permission and keeps 24 hours of metrics. `composer dev` runs Horizon instead of `queue:listen`, so restart its tab after you change job code.
- **Mail intercept** sends every email to the current user when `MAIL_INTERCEPT=true`. Use it on local or staging copies with real data, so no email reaches a real recipient. If there is no current user, the email goes to `MAIL_INTERCEPT_ADDRESS`, or it is dropped when that is empty. Queued emails go to the user who was logged in when they were queued. The intercept is always off in production.
- **Context** carries `trace_id` and `user_id` into every log line and queued job. Each request gets a trace ID, or reuses a valid incoming `X-Request-Id`, and the response returns it in `X-Request-Id`. Activity logged in queued jobs uses the `user_id` from Context as its causer.
- **Daily log files** replace the single log file (`LOG_STACK=daily`).
- **`APP_DOMAIN`** is a new setting. It is available as `config('app.domain')`, and `APP_URL` is built from it.
- **Livewire config** is published to `config/livewire.php`.
- **HTTPS** is forced for all generated URLs, in every environment. Serve the app over HTTPS locally too.
- **Models, factories, seeders, concerns, and actions** live in domain folders. A model's factory sits in the sibling `Factories` folder, for example `App\Domain\User\Factories\UserFactory`. The model links to its factory in `newFactory()`, and the factory links back with `$model`. Seeders extend `App\Domain\Shared\Common\Seeders\Seeder`. Fortify actions have their own `Fortify` folder only to keep them together, for example `App\Domain\User\Actions\Fortify\CreateNewUser`. Create a model with `php artisan make:model Domain/Billing/Models/Invoice`. Write its factory and seeder by hand, because `make:factory` and `make:seeder` still write to `database/`. The listeners in every `Listeners` folder and the Artisan commands in every `Commands` folder under `app/Domain` are registered automatically, at any depth.
- **Actions** hold the business work: one class per operation that uses the `Actionable` trait and runs with `execute()`. Livewire components only validate, authorize, call an Action, and show a toast or redirect. See [Actions](docs/actions.md).
- **The base model**, `App\Domain\Shared\Common\Models\Model`, adds soft deletes and activity logging. A model that extends it needs a `deleted_at` column and an entry in the morph map. The activity log stores the model as a polymorphic subject, so without that entry, creating, updating, deleting, or restoring it throws `ClassMorphViolationException`. The log records changes to fillable attributes, but not to attributes in `$hidden`. Keep secrets in `$hidden`.
- **Strict models** are on outside production. Lazy loading, reading a missing attribute, or mass-assigning an attribute that is not fillable throws an exception.
- **Morph map** is enforced, and `user` maps to `App\Domain\User\Models\User`. Add each new polymorphic model to the map in `EloquentServiceProvider`.
- **Blaze** optimizes the Blade components in `resources/views/components`.
- **Tests** use Pest syntax and run with test impact analysis.
- **Code checks** are stricter. PHPStan runs at level 10 with extra Larastan checks and Livewire support. Rector runs with Laravel and Pest rules. Vite+ lints the JavaScript and formats the JavaScript and CSS in `resources/`. Prettier formats the Blade views and sorts their Tailwind classes.
- **Workflows** use Spatie Model States. A base state extends `App\Domain\Shared\Common\States\State`, which gives labels, colors, and the next states that a user can move to. It lists its transitions in `configure()`, and the kit builds that config once per state column and keeps it, so a flow with many states stays fast, also on Octane. A transition class extends `App\Domain\Shared\Common\Transitions\Transition`, which saves in a transaction, and fails when the state changed after the model was loaded or when a listener stopped the save. Callers check the `transition` policy ability before each step. A Livewire component runs a step with its own `transitionTo()`, which shows a toast instead of an error page when the step fails. See [Workflows](docs/workflows.md).
- **Docs** in `docs/` follow the code. `php artisan docs:generate` fills each section between `<!-- generated:name -->` and `<!-- /generated:name -->` from the code, for example the permission matrix and the rules that the test names state. It runs the migrations and seeders on an in-memory SQLite database, so the matrix shows the roles that the seeders create. `composer test` fails when a section is out of date, or when a doc names a file, class, translation key, permission, route, config key, table column, or environment variable in backticks that does not exist. See [Permissions](docs/modules/permission.md).

## Requirements

- PHP 8.5+ with the `phpredis`, `exif`, `gd`, `bcmath`, `pdo_pgsql`, `pcntl`, and `posix` extensions
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

2. Make sure Redis runs on `127.0.0.1:6379`, because the seeders in the next step use the cache. If Redis runs elsewhere, copy `.env.example` to `.env` and change the `REDIS_*` values first.

3. Install dependencies, create `.env`, generate the app key, run migrations, seed the database, create the storage links, and build assets:

   ```bash
   composer setup
   ```

   Log in as `admin@example.com` (super admin) or `it@example.com` (no roles). Both have the password `password`.

4. If you serve the app from a different host, set `APP_DOMAIN` in `.env`.

5. If you use Junie, write its Boost MCP config. git ignores this one file, because it holds local paths. The other Boost files are committed:

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

| Command                | Description                                                                                        |
|------------------------|----------------------------------------------------------------------------------------------------|
| `composer test`        | Clear the config cache, run `lint:check`, `types:check`, and `docs:check`, then run the Pest tests |
| `composer lint`        | Fix code with Rector, Pint, the Vite+ linter and formatter, and Prettier                           |
| `composer lint:check`  | Check Pint, Rector, Vite+ linting and formatting, and Prettier without changes                     |
| `composer types:check` | Run PHPStan (Larastan) at level 10                                                                 |
| `composer docs`        | Fill the generated sections of the docs from the code                                              |
| `composer docs:check`  | Fail when a generated section is out of date or a doc names code that does not exist               |
| `composer ci:check`    | Run `composer test` with no process timeout, as CI does                                            |

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

## Deployment

Keep these processes running on the server:

- `php artisan horizon`, under a process monitor. For Supervisor, copy `deploy/supervisor/horizon.conf` to `/etc/supervisor/conf.d/`, replace its app path and user, and run `sudo supervisorctl reread`, `sudo supervisorctl update`, and `sudo supervisorctl start horizon`. On Laravel Cloud or Forge, add it as a background process instead. Keep the stop timeout (`stopwaitsecs`) above the longest job timeout, which is 600 seconds for media conversions.
- The scheduler, every minute: `* * * * * php artisan schedule:run`. It takes the Horizon metric snapshots.

After each deploy:

1. Run `php artisan optimize`. It caches the config, events, routes, and views, so the app does not search `app/Domain` for listeners on each request.
2. Run `php artisan horizon:terminate`. Fast termination is on, so the process monitor starts a new Horizon with the new code right away, while the old workers finish their current jobs.

## License

MIT
