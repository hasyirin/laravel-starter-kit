#!/usr/bin/env bash

# Braces + the trailing exit keep bash from re-reading this file after `git pull`
# overwrites it mid-run. Do not add anything below the closing brace.
{
    set -euo pipefail

    if [[ $EUID -eq 0 ]]; then
        echo 'Do not run this script as root. Run it as the deploy user.' >&2
        exit 1
    fi

    cd "$(dirname "${BASH_SOURCE[0]}")/.."

    OWNER="$(id -un)"
    STAGE=before-down

    SEEDERS=(
        'App\Domain\Shared\Permission\Seeders\PermissionSeeder'
        'App\Domain\Shared\Permission\Seeders\RoleSeeder'
    )

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

    report_stop() {
        case $STAGE in
            before-down)
                echo 'The update stopped before php artisan down finished.' >&2
                if [[ -e storage/framework/down ]]; then
                    echo 'The app is still in maintenance mode from an earlier run.' >&2
                fi
                echo 'Fix the error, then run deploy/update.sh again.' >&2
                ;;
            down)
                echo 'The update stopped. The app is still in maintenance mode.' >&2
                echo 'The update runs git reset --hard, which discards edits to tracked files on the server.' >&2
                echo 'Fix the error, then run deploy/update.sh again.' >&2
                echo 'To bring the app back up now, run php artisan up.' >&2
                echo 'Warning: the code can be partly updated: pulled, but not installed, built, or migrated.' >&2
                ;;
        esac
    }

    trap 'report_stop; restore_permissions' EXIT

    restore_permissions

    git fetch

    if ! UPSTREAM="$(git rev-parse --abbrev-ref --symbolic-full-name '@{upstream}')"; then
        echo 'The current branch has no upstream branch. Set one with git branch --set-upstream-to.' >&2
        exit 1
    fi

    if ! git merge-base --is-ancestor HEAD "$UPSTREAM"; then
        echo "$UPSTREAM is not a fast-forward from HEAD. Make the server branch match it by hand." >&2
        exit 1
    fi

    [[ -e storage/framework/down ]] || php artisan down --render='errors::503'
    STAGE=down

    rm -f bootstrap/cache/*.php

    git reset --hard
    git pull --ff-only

    composer install --optimize-autoloader --no-dev --no-interaction

    bun install --frozen-lockfile
    bun run build
    PUPPETEER_CACHE_DIR="$PWD/.cache/puppeteer" bunx puppeteer browsers install chrome-headless-shell

    php artisan migrate --force

    for seeder in "${SEEDERS[@]}"; do
        php artisan db:seed --force --class="$seeder"
    done

    restore_permissions
    sudo -u www-data php artisan optimize
    sudo -u www-data php artisan horizon:terminate

    php artisan up
    STAGE=up

    exit 0
}
