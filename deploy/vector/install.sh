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
OPENOBSERVE_ORG="${OPENOBSERVE_ORG:-default}"
OPENOBSERVE_STREAM="${OPENOBSERVE_STREAM:-$APP_NAME}"

: "${OPENOBSERVE_URL:?Set OPENOBSERVE_URL, for example https://openobserve.example.com}"
: "${OPENOBSERVE_USER:?Set OPENOBSERVE_USER to the email of the ingestion user}"

env_file=/etc/default/vector
conf_glob=/etc/vector/conf.d/*.yaml
app_config="/etc/vector/conf.d/$APP_NAME.yaml"

if [[ -f $env_file ]] && grep -Eq '^\s*(export\s+)?VECTOR_CONFIG_(DIR|YAML|TOML|JSON)=' "$env_file"; then
    echo "$env_file sets its own config folder or files. Add $app_config to it yourself." >&2
    exit 1
fi

if [[ -z "${OPENOBSERVE_PASSWORD:-}" ]]; then
    read -rsp "OpenObserve password or ingestion token: " OPENOBSERVE_PASSWORD
    echo
fi

if ! command -v vector > /dev/null; then
    bash -c "$(curl -fsSL https://setup.vector.dev)"
    apt-get install -y vector
    if [[ -f /etc/vector/vector.yaml ]]; then
        mv /etc/vector/vector.yaml /etc/vector/vector.yaml.example
    fi
fi

restart=false

storage="$APP_PATH/storage"
group="$(stat -L -c %G "$storage")"
install -d -o "$(stat -L -c %U "$storage")" -g "$group" -m 0775 "$storage/logs/json"
if ! id -nG vector | tr ' ' '\n' | grep -qx "$group"; then
    usermod -aG "$group" vector
    restart=true
fi

OPENOBSERVE_URL="${OPENOBSERVE_URL%/}"
config="$(< "$directory/vector.yaml")"
for name in APP_NAME APP_PATH OPENOBSERVE_URL OPENOBSERVE_ORG OPENOBSERVE_STREAM OPENOBSERVE_USER OPENOBSERVE_PASSWORD; do
    config="${config//"\${$name}"/"${!name}"}"
done

candidate="$(mktemp --suffix=.yaml)"
previous="$(mktemp)"
trap 'rm -f "$candidate" "$previous"' EXIT
printf '%s\n' "$config" > "$candidate"
vector validate "$candidate"

touch "$env_file"
current="$(sed -n 's/^VECTOR_CONFIG=//p' "$env_file" | tail -n 1)"
if [[ -z $current ]]; then
    for default in /etc/vector/vector.yaml /etc/vector/vector.toml; do
        if [[ -f $default ]]; then
            current="$default"
            break
        fi
    done
fi
case ",$current," in
    *",$conf_glob,"*) paths="$current" ;;
    ,,) paths="$conf_glob" ;;
    *) paths="$current,$conf_glob" ;;
esac

had_previous=false
if [[ -f $app_config ]]; then
    cp -p "$app_config" "$previous"
    had_previous=true
fi
install -d -m 0755 /etc/vector/conf.d
install -m 0640 -o root -g vector "$candidate" "$app_config"

if ! VECTOR_CONFIG="$paths" vector validate --no-environment; then
    if $had_previous; then
        cp -p "$previous" "$app_config"
    else
        rm -f "$app_config"
    fi
    echo "The config files on this server are not valid together, so $app_config was not applied." >&2
    exit 1
fi

if ! grep -qxF "VECTOR_CONFIG=$paths" "$env_file"; then
    sed -i '/^VECTOR_CONFIG=/d' "$env_file"
    echo "VECTOR_CONFIG=$paths" >> "$env_file"
    restart=true
fi

systemctl enable vector
if $restart || ! systemctl is-active -q vector; then
    systemctl restart vector
else
    systemctl reload vector
fi
systemctl --no-pager status vector
