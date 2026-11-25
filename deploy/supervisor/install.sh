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
APP_USER="${APP_USER:-www-data}"

program="$APP_NAME-horizon"
app_config="/etc/supervisor/conf.d/$program.conf"

if [[ ! -f $APP_PATH/artisan ]]; then
    echo "$APP_PATH/artisan does not exist. Set APP_PATH to the app folder." >&2
    exit 1
fi

if ! id -u "$APP_USER" > /dev/null 2>&1; then
    echo "The user $APP_USER does not exist. Set APP_USER to the user that runs the app." >&2
    exit 1
fi

if ! command -v supervisorctl > /dev/null; then
    apt-get install -y supervisor
    systemctl enable --now supervisor
fi

config="$(< "$directory/horizon.conf")"
for name in APP_NAME APP_PATH APP_USER; do
    config="${config//"\${$name}"/"${!name}"}"
done

candidate="$(mktemp)"
trap 'rm -f "$candidate"' EXIT
printf '%s\n' "$config" > "$candidate"

if [[ -f $app_config ]] && cmp -s "$candidate" "$app_config"; then
    echo "$app_config has not changed."
else
    install -d -m 0755 /etc/supervisor/conf.d
    install -m 0644 -o root -g root "$candidate" "$app_config"
    supervisorctl reread
fi

supervisorctl update "$program"
state="$(supervisorctl status "$program" || true)"
if ! grep -Eq 'RUNNING|STARTING|BACKOFF' <<< "$state"; then
    supervisorctl start "$program" || true
fi

escaped_path="$(sed 's/[][\\.*^$+?(){}|]/\\&/g' <<< "$APP_PATH")"
shopt -s nullglob
for file in /etc/supervisor/conf.d/*.conf; do
    if [[ $file == "$app_config" ]]; then
        continue
    fi
    if grep -Eq "^\s*command\s*=.*$escaped_path/artisan\s+horizon(\s|\$)" "$file"; then
        programs="$(sed -n 's/^\s*\[program:\([^]]*\)\].*/\1/p' "$file" | paste -sd ' ' -)"
        echo "WARNING: $file also runs Horizon for $APP_PATH, so two Horizon masters run." >&2
        echo "Remove that program from the file, then run: sudo supervisorctl update $programs" >&2
    fi
done

state="$(supervisorctl status "$program" || true)"
echo "$state"
if grep -q RUNNING <<< "$state"; then
    exit 0
fi

echo "$program is not RUNNING yet. If it does not reach RUNNING, read $APP_PATH/storage/logs/horizon.log." >&2
if ! grep -Eq 'STARTING|BACKOFF' <<< "$state"; then
    exit 1
fi
