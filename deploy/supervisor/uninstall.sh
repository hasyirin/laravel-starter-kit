#!/usr/bin/env bash
set -euo pipefail

if [[ $EUID -ne 0 ]]; then
    echo "Run this script as root, for example with sudo." >&2
    exit 1
fi

directory="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

APP_PATH="${APP_PATH:-$(cd "$directory/../.." && pwd)}"
APP_PATH="${APP_PATH%/}"
folder="$(basename "$APP_PATH")"
if [[ $folder == current ]]; then
    folder="$(basename "$(dirname "$APP_PATH")")"
fi
APP_NAME="$(tr '[:upper:]' '[:lower:]' <<< "${APP_NAME:-$folder}" | tr -c 'a-z0-9_\n' '_')"

program="$APP_NAME-horizon"
app_config="/etc/supervisor/conf.d/$program.conf"

if [[ ! -f $app_config ]]; then
    echo "$app_config does not exist. Nothing to remove." >&2
    exit 1
fi

supervisorctl stop "$program" || true
rm "$app_config"
supervisorctl reread
if output="$(supervisorctl update "$program" 2>&1)"; then
    printf '%s\n' "$output"
elif [[ $output == *"no such group"* ]]; then
    echo "Supervisor had not loaded $program, so there was nothing to stop."
else
    printf '%s\n' "$output" >&2
    exit 1
fi
