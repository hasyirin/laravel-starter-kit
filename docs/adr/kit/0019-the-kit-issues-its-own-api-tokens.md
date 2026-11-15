# The Kit Issues Its Own API Tokens

The kit issues API tokens from its own table, `api_tokens`, and checks them with its own guard. A token has the GitHub form: a prefix from `API_TOKEN_PREFIX`, 40 random characters, and a checksum of 6 characters. The app keeps only the SHA-256 hash of a token and its last 4 characters. A system administrator sees the token one time, when the app makes it.

## Considered Options

- **Laravel Sanctum**: rejected. Its guard returns no user for each failure, so the API log cannot state why the app rejected a call. Sanctum also deletes the row when it revokes a token, so a call with a revoked token looks like a call with an unknown token. It is also one more dependency.
- **Laravel Passport**: rejected. OAuth2 is not a requirement, and Passport adds many tables, keys, and routes.

## Consequences

- The API log can show each reason for a rejected call, such as an expired token or a revoked token.
- A revoked token keeps its row, so the API client's page still shows it as revoked.
- The checksum lets the guard reject a mistyped token without a database query. The prefix lets secret scanners find a leaked token.
- An API client is not a user. It has no roles and no permissions, and no policy runs for it. A scope on its token decides what it can call.
- The kit owns this code, so the kit must keep it secure and tested.
