# One Vector Daemon per Server, with a Config File per App

A server can host apps that are not from the kit, and some already ship their own logs with Vector. So the kit does not own the Vector setup of a server. It uses the one Vector service from the package, which is the agent role that Vector recommends for a host. Each app adds only its own file in /etc/vector/conf.d, and the VECTOR_CONFIG line in /etc/default/vector loads that folder next to the files that are already there. `deploy/vector/install.sh` and `deploy/vector/uninstall.sh` change nothing else that other apps own. See `docs/vector.md`.

## Considered Options

- The kit owns /etc/vector/vector.yaml and reads every app under /var/www. Rejected: it replaced the config of other apps, and it made folders in apps that are not from the kit.
- One Vector service per app. Rejected: Vector names this the sidecar role, for teams where each service owns its own collection, and it runs one process for each app on the server.

## Consequences

- All files load into one process, so a file that is not valid stops the logs of every app. The install script validates all files together before it applies its file, and puts back the old file if they fail.
- Component names start with the app name, because a name used in two files stops Vector.
- Vector does not start when the conf.d pattern matches no file. So the uninstall script removes the folder from VECTOR_CONFIG when it deletes the last file.
