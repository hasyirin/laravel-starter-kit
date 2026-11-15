# Calls to Services Go Through Saloon Connectors

The app calls each service through a Saloon connector that extends `ServiceConnector`. The connector reads the base URL, the timeouts, and the retry settings of the service from `system.api.services`. Each endpoint of the service is a request class that extends `ServiceRequest`. The connector writes one API log row for each attempt of each call. See [API](../../api.md).

Saloon gives each service a structure: one connector for the service, and one request class for each endpoint. A developer finds all the calls to a service in one place. Saloon also has authenticators, OAuth2 grants, pagination, and test fixtures, so an app does not write them again for each service. Saloon sends each attempt as a new request, so each attempt gets its own row and its own duration. Since Saloon 4, a request cannot replace the base URL with an absolute URL. So it cannot send the credentials of a service to another host.

## Considered Options

- **A macro on Laravel's HTTP client**: rejected. The kit had an `Http` macro named service, and it worked. But it gave a service no structure. The calls of a service were loose URLs and arrays across the app, and each app wrote its own classes for credentials and endpoints.

## Consequences

- The app has two styles of HTTP calls. A call with Laravel's `Http` client does not go to the API log.
- `Http::fake()` does not catch a call through a connector. A test of such a call uses the mock client of Saloon.
- An asynchronous call, such as a call in a Saloon pool, writes an API log row only when it gets a status below 400. For an error status or a failed connection, Saloon runs no middleware that can write the row.
- The kit depends on Saloon, so it must move to each new major version of Saloon.
