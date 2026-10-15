# Laravel Template

A personal starting point for new Laravel projects. It is based on the official Laravel Livewire starter kit, with extra packages and different environment defaults.

## Stack

- PHP 8.3+, Laravel 13
- Livewire 4, Flux and Flux Pro, Blaze
- Fortify for authentication, with passkey support
- Spatie Activitylog to log model and user activity
- Tailwind CSS 4, Vite (via Vite+)
- Pest, Larastan, and Pint
- Debugbar and Ray for local debugging
- Laravel Boost for AI-assisted development

## Differences From the Starter Kit

- **Flux Pro** is installed from `composer.fluxui.dev`. You need a Flux Pro license.
- **Debugbar**, **Ray**, and **Laravel Boost** are installed as dev dependencies.
- **Spatie Activitylog** is installed. Its migration creates the `activity_log` table.
- **Redis** is the default driver for session, cache, and queue.
- **Daily log files** replace the single log file (`LOG_STACK=daily`).
- **`APP_DOMAIN`** is a new setting. It is available as `config('app.domain')`, and `APP_URL` is built from it.
- **Livewire config** is published to `config/livewire.php`.

## Requirements

- PHP 8.3+ with the `phpredis` extension
- Composer 2
- Node.js 22+
- Redis
- A Flux Pro license

## Setup

1. Add your Flux Pro credentials. This creates `auth.json`, which git ignores:

   ```bash
   composer config http-basic.composer.fluxui.dev "<your-email>" "<your-license-key>"
   ```

2. Install dependencies, create `.env`, generate the app key, run migrations, and build assets:

   ```bash
   composer setup
   ```

3. Make sure Redis runs on `127.0.0.1:6379`, or change the `REDIS_*` values in `.env`.

4. If you serve the app from a different host, set `APP_DOMAIN` in `.env`.

5. If you use an AI coding agent, generate the Boost guidelines, skills, and MCP config. git ignores the generated files:

   ```bash
   php artisan boost:install
   ```

## Development

```bash
composer dev
```

## Quality Checks

| Command                 | Description                                       |
|-------------------------|---------------------------------------------------|
| `composer test`         | Run Pint (check mode), PHPStan, and the Pest tests |
| `composer lint`         | Fix code style with Pint                          |
| `composer lint:check`   | Check code style without changes                  |
| `composer types:check`  | Run PHPStan (Larastan)                            |

## License

MIT
