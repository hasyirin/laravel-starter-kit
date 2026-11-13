# API

The app has an API that API clients call with an API token. The app also calls services. The API log records each call in both directions. The [glossary](../GLOSSARY.md#api) defines the terms: API client, service, API token, scope, allowed IP address, and API log. See [kit ADR-0016](adr/kit/0016-the-kit-issues-its-own-api-tokens.md), [kit ADR-0017](adr/kit/0017-one-api-log-records-both-directions.md), and [kit ADR-0018](adr/kit/0018-calls-to-services-go-through-saloon-connectors.md).

## Register an API Client

A system administrator registers each API client at `/system/api-clients`. The page is in the system area, so it needs `system.access`. The permissions are in Permissions, below.

An API client has these fields:

- **Name**: unique among the API clients that are not deleted.
- **Description**: optional.
- **Enabled**: on by default. When it is off, the API rejects each call of the API client. The app still calls the service of the API client.
- **Allowed IP addresses**: one row for each IPv4 or IPv6 address or CIDR range, with an optional description of at most 255 characters. The form refuses an empty address, an address that is in the list two times, and a range with the prefix `/0`, such as 0.0.0.0/0. `api_clients.allowed_ip_addresses` holds the rows as a JSON list. Each item has the keys address and description.
- **Allow any IP address**: off by default. When it is on, each IP address can call with an API token of the API client, and the page shows a warning.
- **Rate limit**: the calls per minute that the API client can make, from 1 to 100,000. The default is 60.
- **Service**: optional. See Link an API Client to a Service, below.

If the list of allowed IP addresses is empty and "Allow any IP address" is off, the API rejects every call of the API client. There is no exception for localhost.

A delete is a soft delete. It revokes the active API tokens of the API client first. The API log rows keep the name of the API client.

The changes to an API client and to its API tokens go to the audit log. The Audit tab of the API client shows both. The API log tab shows the API log rows of the API client.

## API Tokens

A system administrator issues API tokens on the API tokens tab of an API client. This needs `system.api-clients.update`.

- **Format**: the prefix, an underscore, 40 random letters and digits, and a checksum of 6 letters and digits. `API_TOKEN_PREFIX` sets the prefix. The default is `app`, so a token looks like `app_` and then 46 letters and digits.
- **Prefix change**: the API checks the prefix of each token. If you change `API_TOKEN_PREFIX`, each API token that exists stops working, and its calls get 401. Issue new API tokens after the change.
- **Shown once**: the dialog shows the plain token one time, when the system administrator issues it or confirms a rotate. The app keeps only a SHA-256 hash and the last 4 characters. Nobody can see the token again.
- **Scopes**: the system administrator selects the scopes of each API token when they issue it. The list comes from `system.api.scopes`. The kit ships an empty list.
- **Expiry**: an API token expires after 365 days (`system.api.token_lifetime_days`). "Never expires" is a choice in the dialog. The tab marks an API token that expires within 30 days.
- **Limit**: an API client can have at most 2 active API tokens (`system.api.max_active_tokens`). An active API token is not revoked and not expired.
- **Rotate**: makes a new API token with the same scopes and the same "never expires" choice. The old API token then expires in 7 days (`system.api.rotation_overlap_days`), or at its own expiry if that is sooner. The new API token counts toward the limit. So an API client with 2 active API tokens must revoke one before a rotate.
- **Revoke**: the API rejects the API token from that moment. The row stays, so the tab and the API log still show it.

The API records the last use of an API token at most one time per minute.

An API client sends the API token in the Authorization header:

```http
GET /api/v1/ping HTTP/1.1
Authorization: Bearer app_...
```

## Add a Route

Add each route to `routes/api.php`. The file puts each route under `/api/v1` and gives it a name that starts with `api.v1.`. The example route is `api.v1.ping`, at `GET /api/v1/ping`.

```php
// routes/api.php
Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('ping', PingController::class)->name('ping');
    Route::get('orders', ListOrdersController::class)->name('orders.index')->middleware('scope:orders.read');
});
```

Each route in the file gets these middleware from the `api` group, in this order:

1. `AuthenticateApiClient` reads the API token and checks the API client. It also checks the enabled switch and the allowed IP addresses.
2. The `api-client` rate limiter applies the rate limit of the API client.

In these routes, `auth()->user()` returns the `ApiClient`. The `api` guard sets it.

### Scopes

1. Add each scope to `system.api.scopes` in `config/system.php`, as a list of names:

    ```php
    'scopes' => ['orders.read', 'orders.write'],
    ```

2. Check a scope on the route with the `scope` middleware, for example `scope:orders.read`. With more than one name, such as `scope:orders.read,orders.write`, the API token must have each scope.
3. Or check a scope in the code with `ApiClient::tokenCan()`:

    ```php
    abort_unless(auth()->user()->tokenCan('orders.write'), 403);
    ```

    The API log shows such a 403 as Client error, not as Missing scope. So use the middleware when the whole route needs the scope.

An API client is not a `User`. It has no roles and no permissions. Gates and policies are not for API clients: `Gate::before` in `AppServiceProvider` gives no answer for an API client, and each policy of the kit takes a `User`. So check scopes only, with the middleware or with `tokenCan()`.

## Status Codes and Errors

| Status | When |
| --- | --- |
| 401 | The API token is missing, malformed, unknown, expired, or revoked. The response has the header `WWW-Authenticate: Bearer`. |
| 403 | The API client is disabled, the IP address is not allowed, or the API token does not have the scope. |
| 422 | The data of the request is not valid. |
| 429 | The API client went over its rate limit, or the IP address had too many failed authentications. |

Each error on a path under `/api/` uses the application/problem+json format of RFC 9457:

```json
{
    "type": "about:blank",
    "title": "Forbidden",
    "status": 403,
    "detail": "The API client cannot make this request.",
    "trace_id": "0193..."
}
```

- `detail` comes from `lang/en/api/problems.php`. It is the same for each reason of a 401 or a 403, so the response does not tell a caller why the API rejected the call. The API log row has the reason.
- `trace_id` is the trace ID of the request. The response also sends it in the header `X-Request-Id`.
- A 422 adds `errors`, with the messages for each field.
- A 500 shows the message of the exception only while debug mode is on.

## Limits

- **Rate limit**: the rate limit of each API client counts its calls per minute. Calls that the API rejects for the enabled switch or the IP address do not count.
- **Failed authentications**: each IP address can make 10 calls with a missing or bad API token in a minute (`system.api.failed_authentication_per_minute`). After that, the API answers 429 to each call from that IP address until the minute ends. This includes calls with a valid API token.

## Trusted Proxies

The API gets the IP address of the caller from `request()->ip()`. Behind a proxy or a load balancer, that is the address of the proxy. Set `TRUSTED_PROXIES` to the addresses or CIDR ranges of your proxies, with a comma between them. The app then takes the address of the caller from the `X-Forwarded-For` header. `config/trustedproxy.php` reads the value.

The default is empty, so the app trusts no proxy. This is also true on Laravel Cloud, Forge, and Vapor. If the app runs behind a load balancer or on one of these platforms, set `TRUSTED_PROXIES`. Otherwise each call has the IP address of the proxy.

> [!WARNING]
> Do not set `TRUSTED_PROXIES` to `*`. The app then trusts the `X-Forwarded-For` header of every caller. A caller can then send any IP address and pass the allowed IP address check of each API client. It can also pass the limit of failed authentications.

## Log the Bodies of a Route

The API log stores no bodies by default. To store the request body and the response body of a route, add the `log-bodies` middleware:

```php
Route::post('orders', StoreOrderController::class)->name('orders.store')->middleware(['scope:orders.write', 'log-bodies']);
```

See Bodies and Masking, below, before you turn it on.

## Call a Service

The app calls each service through a [Saloon](https://docs.saloon.dev) connector. See [kit ADR-0018](adr/kit/0018-calls-to-services-go-through-saloon-connectors.md). The API log records each call through a connector that extends `ServiceConnector`.

### Declare a Service

Add each service to `system.api.services` in `config/system.php`. The key is the name of the service:

```php
'services' => [
    'billing' => [
        'base_url' => 'https://billing.example.com',
        'timeout' => 30,
        'connect_timeout' => 10,
        'tries' => 3,
        'retry_interval' => 500,
        'exponential_backoff' => true,
        'log_bodies' => false,
    ],
],
```

| Key | Default | What it does |
| --- | --- | --- |
| `base_url` | None. It is required. | The start of each URL of the service. |
| `timeout` | 30 | The seconds that a call can take. |
| `connect_timeout` | 10 | The seconds that the connection can take. |
| `tries` | 1 | The number of attempts of each call. 1 means no retry. |
| `retry_interval` | 0 | The milliseconds to wait before each retry. |
| `exponential_backoff` | `false` | Whether the wait doubles before each next retry. With the example above, the waits are 500 and 1000 milliseconds. |
| `log_bodies` | `false` | Whether the API log stores the request body and the response body. |

A connector retries a call that cannot connect, and a call with a status of 400 or more. After the last attempt, a call with an error status returns the last response and does not throw. A call that cannot connect throws `Saloon\Exceptions\Request\FatalRequestException`. A request class can set its own `$tries`, `$retryInterval`, and `$useExponentialBackoff`. Its values come before the values of the service.

### Write a Connector and Requests

Put the connector of a service in the Connectors folder of its domain, such as app/Domain/Billing/Connectors/BillingConnector.php. Put its request classes in the Requests folder next to it. Write them by hand. `php artisan saloon:connector` and `php artisan saloon:request` write to app/Http/Integrations, and their classes extend the Saloon classes, not the kit's classes.

The connector extends `ServiceConnector` and returns the name of the service from `service()`. It reads the base URL, the timeouts, and the retry settings from config. It also sends the trace ID of the request or the job in the header `X-Request-Id`. A name that is not in `system.api.services` throws a `LogicException` at the first call.

```php
class BillingConnector extends ServiceConnector
{
    public function service(): string
    {
        return 'billing';
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator(config()->string('services.billing.token'));
    }
}
```

Each request class extends `ServiceRequest`, and gives the method and the endpoint. The endpoint is a path. Saloon refuses an absolute URL, so a request cannot send the credentials of the service to another host.

```php
class CreateBill extends ServiceRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(private readonly Invoice $invoice) {}

    public function resolveEndpoint(): string
    {
        return '/v1/bills';
    }

    protected function defaultBody(): array
    {
        return ['amount' => $this->invoice->total];
    }
}
```

A response middleware that a connector or a request registers with `Saloon\Enums\PipeOrder::FIRST` runs before the API log records the call. If it throws, the call leaves no row.

### Credentials

`ServiceConnector` adds no credentials. Keep the credentials of a service in the environment, and read them in `config/services.php`, as for an SMS gateway (see [kit ADR-0014](adr/kit/0014-the-kit-sends-sms-through-its-own-channel.md)). Give them to the connector with a Saloon authenticator in `defaultAuth()`, as in the example above. For OAuth2, use the Saloon OAuth2 traits on the connector.

### Make a Call

Send a request through the connector. Add `about()` to link the API log row to a record:

```php
$response = new BillingConnector()->send(new CreateBill($invoice)->about($invoice));
```

`about()` belongs to the request object, so the next request does not get the record. If you send the same request object again, its rows get the same record.

A call made with `Http::get()` or another method of `Http` leaves no row. A call through a connector that extends Saloon's `Saloon\Http\Connector` directly leaves no row either.

The app writes the API log row of a call in the database transaction of the caller. If the transaction rolls back, the row goes too. If the row must stay, call the service outside a transaction.

> [!WARNING]
> An asynchronous call, in a Saloon pool or with `sendAsync()`, leaves a row only when it gets a status below 400. If it gets an error status or cannot connect, it leaves no row, because Saloon then runs no response middleware and no fatal exception middleware. Saloon also does not retry an asynchronous call. Use `send()` for calls that the API log must show.

### Test a Call

`Http::fake()` does not catch a call through a connector. Use `Saloon::fake()` from `Saloon\Laravel\Facades\Saloon` with `Saloon\Http\Faking\MockResponse` objects. A list of responses answers the calls in order:

```php
Saloon::fake([MockResponse::make(['id' => 7], 201)]);
```

A test that calls a service without a fake fails with a `Saloon\Exceptions\StrayRequestException`. The request is not sent.

The API log still records each faked call. To fake a call that cannot connect, make the response throw a `Saloon\Exceptions\Request\FatalRequestException`. `tests/Feature/Domain/Shared/Api/Connectors/ServiceConnectorTest.php` shows how.

### Link an API Client to a Service

A system administrator can link an API client to one service on its page. The Service field shows when the app declares a service. It also shows a linked service that the app no longer declares. The page then cannot save until a system administrator clears the field. Each service can be linked to one API client at most. The API log rows of calls to that service then also show the API client. The enabled switch of the API client does not stop these calls.

## API Log

System administrators read the API log at `/system/logs/api`. The page needs `system.access` and `system.logs.api.view`. It lists the calls of both directions, the newest first. Filter the list by direction, outcome, API client, service, status class, and date. Search it by URL. Each row opens a dialog that shows each stored field.

### What a Row Holds

The API log has one row for each call to a path under `/api/`, also for each call that the API rejects. It also has one row for each attempt of each call through a connector that extends `ServiceConnector`, also for an attempt that cannot connect.

A row holds:

- The direction: inbound or outbound.
- The API client and its name. The row keeps the name, so it still shows the name after a delete. A call with an unknown API token has no API client.
- The API token of an inbound call, also an expired or revoked one.
- The service and the linked record of an outbound call.
- The method, the URL, and the route name. An inbound URL is the path only. An outbound URL has no user name, no password, and no query string, because they can hold credentials.
- The status, the outcome, and the duration in milliseconds. A call that cannot connect has no status.
- The IP address and the user agent of an inbound call.
- The trace ID.
- The sizes of the request body and the response body, in bytes. An inbound call takes the request size from its Content-Length header. Without that header, the row has no request size.
- The bodies, if the route or the service turns them on.
- The error of an outbound call that cannot connect.

The row stores no header, except the user agent of an inbound call. So it never stores the Authorization header or the API token.

The duration of an outbound row is the time of its own attempt. It does not include the wait before a retry. It does include the request delay of Saloon and the time of the request middleware, such as an authenticator or `boot()` of the connector.

> [!WARNING]
> An outbound URL loses only its user name, its password, and its query string. The API log stores the path as it is, also the path of `base_url`. So never put a secret in the path.

The API log rows do not go to the audit log.

### Outcomes

| Outcome | Status | Reason |
| --- | --- | --- |
| OK | Below 400 | The call worked. |
| Missing token | 401 | The call has no API token. |
| Invalid token | 401 | The API token is malformed or unknown, or its API client is deleted. |
| Expired token | 401 | The API token expired. |
| Revoked token | 401 | The API token is revoked. |
| API client disabled | 403 | The enabled switch of the API client is off. |
| IP address not allowed | 403 | The IP address is not in the allowed IP addresses. |
| Missing scope | 403 | The API token does not have the scope of the route. |
| Rate limited | 429 | The call went over a limit. |
| Validation failed | 422 | The data is not valid. |
| Client error | Other 4xx | For example 404. |
| Server error | 5xx | The server failed. |
| Connection failed | None | An outbound call could not connect. |

### Bodies and Masking

The API log stores the bodies only for a route with `log-bodies` or a service with `log_bodies` on. Before it stores a body, it does these steps:

1. It replaces each byte that is not UTF-8 with a question mark, and it removes each NUL byte.
2. It masks the listed keys of a JSON body. The value of each such key, at any depth, becomes `[hidden]`. `system.api_log.masked_keys` lists the keys. The default list is `password`, `token`, `secret`, `authorization`, `apikey`, and `privatekey`. A key matches when it contains a listed key, without case, `_`, or `-`. So `access_token` and `api-key` match too.
3. It cuts the body to 16,384 bytes (`system.api_log.body_limit`).

A masked JSON body keeps its empty objects and the zero fraction of each number, such as 1.0. An integer that is too large for PHP becomes a string in quotes. If a body starts with { or [ but the app cannot decode it as JSON, the API log stores no body. An example is JSON that is nested more than 512 levels deep.

An outbound form body goes to the log as JSON, so its keys are masked too. An outbound multipart body is never stored, because it can hold files. An outbound stream body is never stored either, because the app can read it only once. For the same reason, a streamed response body is not stored, and its size is stored only when the stream knows it.

> [!WARNING]
> The API log stores any other body without a mask. This includes an inbound form body, an inbound multipart body, XML, and plain text. Keep body logging off for a route or a service that sends such bodies with secrets.

### Retention

`API_LOG_RETENTION_DAYS` sets how many days the log keeps a row. The default is 365. If you set it to an empty value, the log keeps every row.

`ApplyApiLogRetention` runs each day on `API_LOG_QUEUE_CONNECTION`. It writes the older rows to a JSONL file in the folder api-log on the `API_LOG_ARCHIVE_DISK` disk. Then it deletes the rows. It handles 5,000 rows in each job, and it starts a new job for the rest.

## Permissions

| Permission | What it allows |
| --- | --- |
| `system.api-clients.view-any` | Open the list of API clients, and each API client. |
| `system.api-clients.view` | Open an API client. |
| `system.api-clients.create` | Register an API client. |
| `system.api-clients.update` | Change an API client, and issue, rotate, and revoke its API tokens. |
| `system.api-clients.delete` | Delete an API client. |
| `system.api-clients.view-audit-log` | See the Audit tab of an API client. |
| `system.logs.api.view` | Open the API log, and see the API log tab of an API client. |

Each permission also needs `system.access`, because the pages are in the system area. See [Permissions](modules/permission.md).
