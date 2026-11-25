# Run Horizon with Supervisor

Horizon runs the queue workers of the app. It must run all the time, so a process monitor keeps it running. Supervisor starts Horizon when the server starts, and starts it again when it stops.

## How Supervisor Runs on a Server

A server runs one Supervisor service. Other apps on the server can use the same service.

Each app adds its own config file in /etc/supervisor/conf.d, for example /etc/supervisor/conf.d/billing-horizon.conf. The file has one program, for example billing-horizon. The app owns only this file and this program. The scripts do not change the files or the programs of other apps, and they do not stop the Supervisor service.

The program:

- Runs php artisan horizon in the app folder as the user that runs the app.
- Writes the output of Horizon to storage/logs/horizon.log. Supervisor keeps 5 old files of 10 MB.
- Waits up to 3600 seconds (stopwaitsecs) for Horizon to stop. Keep this value above the longest job timeout in `config/horizon.php`, which is 600 seconds for media conversions. Otherwise Supervisor kills a job before it ends.

The files are `deploy/supervisor/horizon.conf` (the config of one app), `deploy/supervisor/install.sh`, and `deploy/supervisor/uninstall.sh`.

## Before You Start

You need:

- A Debian or Ubuntu server with systemd, with the app deployed on it.
- Redis, set in the `.env` of the app. Horizon uses it for the queues.

## Install

Run the script as root from the app folder:

```bash
sudo deploy/supervisor/install.sh
```

You can also set these values:

| Value | Default |
| --- | --- |
| APP_PATH | The app folder that contains the script |
| APP_NAME | The name of the app folder. If the folder is a current release link, the name of the folder above it. |
| APP_USER | www-data. This is the user that runs the app. |

For example:

```bash
sudo APP_USER=forge deploy/supervisor/install.sh
```

The script changes APP_NAME to lowercase, and changes each character other than a letter, a digit, or an underscore to an underscore. For example, /home/forge/example.com/current gives example_com, and the program example_com-horizon. Each app on a server must have a different APP_NAME.

The script:

1. Makes sure that APP_PATH contains artisan and that APP_USER exists.
2. Installs Supervisor with apt-get, if it is not installed. It then enables and starts the Supervisor service.
3. Fills in the values in the config.
4. Writes the config to /etc/supervisor/conf.d/APP_NAME-horizon.conf. Root owns the file, and all users can read it.
5. Tells Supervisor to read the config again, and applies the change to the program of the app only. A new program starts. If the config of a running program changed, Supervisor stops the program and starts it again.
6. Starts the program, if its state is not RUNNING, STARTING, or BACKOFF.
7. Shows the status of the program. If the state is not RUNNING, it shows the path of storage/logs/horizon.log. It then exits with status 1, unless the state is STARTING or BACKOFF.

If the config did not change, the script says so. It then only makes sure that the program runs.

## Remove an Old Setup

An older setup has a file that you copied by hand, for example /etc/supervisor/conf.d/horizon.conf with the program horizon. The script does not delete it. If another program runs Horizon for the same APP_PATH, the script shows a warning with the file name. Then two Horizon masters run. Remove that program from the file, and run the command that the warning shows, for example:

```bash
sudo supervisorctl update horizon
```

## Deploy New Code

`deploy/update.sh` runs php artisan horizon:terminate after the update. Horizon finishes its current jobs and stops. Supervisor then starts a new Horizon with the new code. If you deploy another way, run the command yourself after each deploy.

## Change a Value

Run the install script again with the new values. If you change `deploy/supervisor/horizon.conf`, run the script again too. Do not edit the file in /etc/supervisor/conf.d on the server, because the next run of the script replaces it.

When the config changes, Supervisor stops Horizon and starts it again. Horizon first finishes its current jobs, so the script can wait for some minutes.

If you change APP_NAME, the script makes a new program. Remove the old program first with the uninstall script and the old APP_NAME. Otherwise two Horizon masters run.

## Remove an App

```bash
sudo deploy/supervisor/uninstall.sh
```

The script stops the program of the app, deletes its config file, and removes the program from Supervisor. The Supervisor service continues to run for the other apps.

## Troubleshoot

Show the status of the program and the latest output of Horizon:

```bash
sudo supervisorctl status APP_NAME-horizon
tail -n 100 /var/www/app/storage/logs/horizon.log
```

| Problem | Fix |
| --- | --- |
| The status is FATAL or BACKOFF | Horizon stops right after it starts. Read the log of the program for the error. |
| "no such file" for the Supervisor socket | The Supervisor service does not run. Start it with sudo systemctl start supervisor. |
| Jobs run two times, or the Horizon dashboard shows two masters | Another program runs Horizon for the same app. Remove it, see Remove an Old Setup. |
| Jobs run old code after a deploy | Run php artisan horizon:terminate. |
