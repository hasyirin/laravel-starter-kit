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

env_file=/etc/default/vector
conf_glob=/etc/vector/conf.d/*.yaml
app_config="/etc/vector/conf.d/$APP_NAME.yaml"

if [[ ! -f $app_config ]]; then
    echo "$app_config does not exist. Nothing to remove." >&2
    exit 1
fi
rm "$app_config"

shopt -s nullglob
remaining=(/etc/vector/conf.d/*.yaml)
if [[ ${#remaining[@]} -gt 0 ]]; then
    systemctl reload vector
    exit 0
fi

current="$(sed -n 's/^VECTOR_CONFIG=//p' "$env_file" | tail -n 1)"
paths="$(tr ',' '\n' <<< "$current" | { grep -vxF "$conf_glob" || true; } | paste -sd, -)"
sed -i '/^VECTOR_CONFIG=/d' "$env_file"

if [[ -z $paths ]]; then
    systemctl disable --now vector
    echo "No Vector config is left, so the Vector service is stopped and disabled."
    exit 0
fi

echo "VECTOR_CONFIG=$paths" >> "$env_file"
systemctl restart vector
