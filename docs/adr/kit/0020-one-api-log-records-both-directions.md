# One API Log Records Both Directions

The kit keeps one log, the API log, for each call to the app's API and each call from the app to a service. Each row is one call. A column states the direction. The API log is apart from the audit log and the message log.

## Considered Options

- **Calls in the audit log**: rejected. The audit log records changes to records. Most calls change no record, and their number would hide the changes.
- **Calls in the message log**: rejected. A message goes to a person. A call goes to a system or comes from one, and has a status code, a duration, and an IP address.
- **One log for each direction**: rejected. A system that calls the app is often also a service that the app calls. Staff would then look in two places for the calls of one system. Each log would also need its own table, page, permission, and retention job.
- **One concept for API clients and services**: rejected. Credentials go in opposite directions. The app issues an API token to an API client, and a service gives its credentials to the app. So a system administrator registers an API client on a screen, and a developer adds a service in config.

## Consequences

- Some columns are for one direction only. The IP address and the user agent are only for inbound calls. The service and the related record are only for outbound calls.
- An API client can link to one service. Then the outbound rows of that service also show the API client.
- One permission and one retention period cover both directions.
- The API log keeps a copy of the API client's name, so a row still names a deleted API client.
- The API log stores a body only when a route or a service asks for it. It caps the size of each body and masks the listed keys.
