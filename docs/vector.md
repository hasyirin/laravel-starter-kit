# Ship Logs with Vector

The app writes each log record two times: as text to the daily log, and as one JSON object per line to the daily-json log. Vector reads the daily-json files and sends the records to OpenObserve. Each app gets its own stream.

The daily-json files are in storage/logs/json, one file for each day, for example laravel-2026-11-04.json. Laravel keeps 3 files. Each record has the message, the context (with the stack trace of an exception), the level, the channel (the app environment), and the time.

## How Vector Runs on a Server

A server runs one Vector service, the daemon from the Vector package. This is the role that Vector recommends to collect logs on a host. Other apps on the server can use the same service, also apps that are not Laravel apps.

Each app adds its own config file in /etc/vector/conf.d, for example /etc/vector/conf.d/billing.yaml. The app owns only this file. The scripts do not change /etc/vector/vector.yaml or the files of other apps. The VECTOR_CONFIG line in /etc/default/vector tells the service to load /etc/vector/vector.yaml (if it exists) and all files in /etc/vector/conf.d.

All files load into one process, so one file that is not valid stops all of them. For this reason, the install script validates all files together before it applies its change. It also gives each Vector component a name that starts with the app name, so the names do not clash with other files.

On the way to OpenObserve, Vector:

- Puts the fields of the JSON record at the top level.
- Adds the app name (app), the server name (host), and the log file path (file).
- Sets the OpenObserve time (_timestamp) to the time of the record, not the time that Vector sent it.
- Keeps up to 256 MB of records on disk when OpenObserve is down, and sends them when it comes back.

The files are `deploy/vector/vector.yaml` (the config of one app), `deploy/vector/install.sh`, and `deploy/vector/uninstall.sh`.

## Before You Start

You need:

- A Debian or Ubuntu server with systemd, with the app deployed on it.
- The URL of OpenObserve and the organization.
- An OpenObserve user for ingestion: its email and its password or ingestion token.
- Logs on the daily-json channel. The stack does this with the line below in the `.env` of the app:

```dotenv
LOG_STACK=daily,daily-json
```

## Install

Run the script as root from the app folder:

```bash
sudo OPENOBSERVE_URL=https://openobserve.example.com \
     OPENOBSERVE_USER=ingest@example.com \
     deploy/vector/install.sh
```

The script asks for the password or token. Do not put it on the command line, because it then goes into the shell history.

You can also set these values:

| Value | Default |
| --- | --- |
| APP_PATH | The app folder that contains the script |
| APP_NAME | The name of the app folder. If the folder is a current release link, the name of the folder above it. |
| OPENOBSERVE_STREAM | APP_NAME |
| OPENOBSERVE_ORG | default |

The script changes APP_NAME to lowercase, and changes each character other than a letter, a digit, or an underscore to an underscore. For example, /home/forge/example.com/current gives example_com. Each app on a server must have a different APP_NAME.

The script:

1. Installs Vector from the Vector apt repository, if it is not installed. It then renames the demo config of the package to /etc/vector/vector.yaml.example.
2. Makes storage/logs/json, and adds the vector user to the group that owns the storage folder, so Vector can read the files.
3. Fills in the values in the config and validates it. This also connects to OpenObserve.
4. Writes the config to /etc/vector/conf.d/APP_NAME.yaml. Only root and the vector group can read the file, because it contains the password.
5. Validates all config files on the server together. If this fails, it puts back the old file of the app and stops.
6. Adds /etc/vector/conf.d to the VECTOR_CONFIG line in /etc/default/vector, if it is not there. It keeps the paths that are already on the line.
7. Reloads the Vector service. It restarts the service only when it changed /etc/default/vector or the groups of the vector user, because a reload does not read them again. Vector continues each file from where it stopped, so a restart does not lose or repeat records.

Vector 0.57 and later do not read environment variables in the config by default. This is why the script writes the values into the config.

If /etc/default/vector sets VECTOR_CONFIG_DIR, VECTOR_CONFIG_YAML, VECTOR_CONFIG_TOML, or VECTOR_CONFIG_JSON, the server loads its config in its own way. The script then stops, and does not change anything. Add the config of the app to that setup yourself.

If another app runs Vector in a different way, for example in Docker or with its own systemd unit, the scripts do not touch it.

## Check That It Works

1. Write a log record:

```bash
php artisan tinker --execute="logger()->info('Vector check')"
```

2. In OpenObserve, open Logs, select the stream of the app, and search for "Vector check". The record comes in a few seconds.

## Change a Value or the Token

Run the install script again with the new values. If you change `deploy/vector/vector.yaml`, run the script again too. Do not edit the file in /etc/vector/conf.d on the server, because the next run of the script replaces it.

## Remove an App

```bash
sudo deploy/vector/uninstall.sh
```

The script deletes the config file of the app and reloads Vector. If it was the last file in /etc/vector/conf.d, it removes the folder from VECTOR_CONFIG, because Vector does not start when the folder has no files. If no other config is left, it stops and disables the Vector service. The vector user stays in the group of the app.

## Upgrade Vector

The upgrade affects all apps on the server.

```bash
sudo apt-get update
sudo apt-get install --only-upgrade vector
sudo systemctl restart vector
```

The service validates all config files before it starts. Read the upgrade guide of each Vector release before you upgrade. Vector sometimes changes defaults, for example the environment variables in 0.57.

## Troubleshoot

Show the status and the latest Vector logs:

```bash
systemctl status vector
journalctl -u vector -n 100 --no-pager
```

| Problem | Fix |
| --- | --- |
| "Health check failed" | Make sure that the server can connect to OPENOBSERVE_URL. |
| "401" or "Unauthorized" in the logs | The user or token is not correct. The health check does not check them. Run the install script again with the correct values. |
| "duplicate source id found" | Two config files use the same component name. Give one of the apps a different APP_NAME. |
| "Permission denied" on a log file | Run the command below with the path of the app. If it fails, give the vector user read access to each folder on the path. |
| No records in OpenObserve | Make sure that LOG_STACK contains daily-json, and that new files appear in storage/logs/json. Clear the config cache after you change `.env`. |
| Records have the wrong time | The record time comes from the datetime field. Check the time zone of the app. |

```bash
sudo -u vector ls /var/www/app/storage/logs/json
```

Vector remembers how far it read each file in /var/lib/vector. After a restart, it continues from that point and does not send records again.
