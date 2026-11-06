# The Area That Runs the App Is the System Area

The system area is the area with the tools that run the app, such as Horizon. A user who can open it is a system administrator. The kit is the base for apps of Malaysian organizations. In those apps, administration (pentadbiran) is office work that staff do in the internal area. So the kit does not use "admin" or "administrator" alone. It leaves these words to the apps.

## Considered Options

- **The admin area**: rejected. It is the common name, for example of the default Filament panel. But an app could then not use "admin" for its own office work, because the word would have two meanings.

## Consequences

- The URL, the route names, and the permissions of the area start with `system`, for example `/system`, `system.dashboard`, and `system.access`.
- Old links to `/admin` answer 404. The kit has no redirect.
- The `system-admin` role keeps its name, short for system administrator. It holds `bypass-authorization`, so its users can open the system area.
- The term system role stays: a role that comes with the app. In both terms, "system" means the app itself. Only permissions decide who can open the system area.
- Do not use "admin" or "administrator" alone for a kit concept.
