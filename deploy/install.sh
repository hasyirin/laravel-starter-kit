#!/usr/bin/env bash

set -euo pipefail

if [[ $EUID -eq 0 ]]; then
    echo 'Do not run this script as root. Run it as the deploy user.' >&2
    exit 1
fi

cd "$(dirname "${BASH_SOURCE[0]}")/.."

OWNER="$(id -un)"
STAGE=before-migrate

restore_permissions() {
    sudo chown -R "$OWNER":www-data .
    sudo chmod -R g+rX,g-w .
    sudo chmod -R 775 storage bootstrap/cache
    sudo find storage/ bootstrap/cache/ -type f -exec chmod 664 {} +
    if [[ -e .env ]]; then
        sudo chmod 640 .env
    fi
    if [[ -e auth.json ]]; then
        sudo chmod 600 auth.json
    fi
}

print_wipe_hint() {
    echo 'To start again, drop all tables:' >&2
    echo '  rm -f bootstrap/cache/*.php' >&2
    echo '  APP_ENV=local php artisan db:wipe --force' >&2
}

report_stop() {
    case $STAGE in
        before-migrate)
            echo 'The install stopped. Fix the error, then run deploy/install.sh again.' >&2
            ;;
        migrate)
            echo 'The install stopped after the migrations started, so the database is not empty.' >&2
            print_wipe_hint
            echo 'Then fix the error, and run deploy/install.sh again.' >&2
            ;;
        optimize)
            echo 'The install stopped, but the database is ready.' >&2
            echo 'Fix the error, then run these commands:' >&2
            echo '  rm -f bootstrap/cache/*.php' >&2
            echo '  sudo -u www-data php artisan optimize' >&2
            ;;
    esac
}

finish() {
    local status=$?
    if [[ $status -ne 0 ]]; then
        report_stop
    fi
    restore_permissions
}

if [[ ! -e .env ]]; then
    cp .env.example .env
    chmod 600 .env
    echo 'Created .env from .env.example.'
    echo 'Fill in .env (database, APP_URL over https, mail, Redis, and so on).'
    echo 'Set APP_ENV=production and APP_DEBUG=false.'
    echo 'Then run deploy/install.sh again.'
    exit 0
fi

if [[ ! -e auth.json && -z "${COMPOSER_AUTH:-}" ]]; then
    echo 'Flux Pro needs credentials. Neither auth.json nor COMPOSER_AUTH exists.' >&2
    echo 'Create auth.json: see "Flux Pro credentials" in README.md (Setup, step 1).' >&2
    exit 1
fi

if ! grep -q '^APP_KEY=' .env; then
    echo '.env has no APP_KEY line. Add the line APP_KEY= to .env, then run deploy/install.sh again.' >&2
    exit 1
fi

trap finish EXIT

restore_permissions

composer install --optimize-autoloader --no-dev --no-interaction

if ! HAS_MIGRATIONS="$(php artisan tinker --execute='echo app("db")->connection()->getSchemaBuilder()->hasTable(config("database.migrations.table")) && app("db")->table(config("database.migrations.table"))->exists() ? "yes" : "no";')"; then
    echo "$HAS_MIGRATIONS" >&2
    echo 'Cannot read the database. Check the database settings in .env.' >&2
    exit 1
fi

if [[ "$HAS_MIGRATIONS" == yes ]]; then
    STAGE=refused
    echo 'The database already has run migrations, so this is not a first install.' >&2
    echo 'The full seed would undo the edits made in the Geography control.' >&2
    echo 'If the app is already installed, use deploy/update.sh.' >&2
    echo 'If an earlier install stopped halfway, drop all tables first, then run deploy/install.sh again.' >&2
    print_wipe_hint
    exit 1
fi

if [[ "$HAS_MIGRATIONS" != no ]]; then
    echo "$HAS_MIGRATIONS" >&2
    echo 'The database check gave an unexpected output.' >&2
    exit 1
fi

if grep -Eq "^APP_KEY=(\"\"|'')?[[:space:]]*$" .env; then
    sed -i 's/^APP_KEY=.*$/APP_KEY=/' .env
    php artisan key:generate --force
fi

bun install --frozen-lockfile
bun run build
PUPPETEER_CACHE_DIR="$PWD/.cache/puppeteer" bunx puppeteer browsers install chrome-headless-shell

php artisan storage:link

STAGE=migrate
php artisan migrate --force
php artisan db:seed --force

STAGE=optimize
rm -f bootstrap/cache/*.php
restore_permissions
sudo -u www-data php artisan optimize

echo
echo 'WARNING: UserSeeder created admin@example.com and it@example.com with the password "password".'
echo 'Change both passwords after the install.'
echo
echo 'Next steps (see README.md, "Deployment"):'
echo '  1. Run Horizon under Supervisor: sudo deploy/supervisor/install.sh'
echo '  2. Add the scheduler cron as www-data, so that www-data can write the logs that the scheduler creates.'
echo '     Run sudo crontab -u www-data -e, and add this line:'
printf '     * * * * * cd %q && php artisan schedule:run >> /dev/null 2>&1\n' "$PWD"
echo '  3. Ship logs with Vector: sudo deploy/vector/install.sh'
echo '  4. For later updates, run deploy/update.sh.'
