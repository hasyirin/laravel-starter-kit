# Log Every Column Except the Hidden Ones

The activity log records the changes to every column of a model, not only the fillable ones. Columns that the app sets directly, for example `email_verified_at` and `two_factor_confirmed_at`, change who can sign in or what a user can do, so the audit must show them. `InteractsWithActivityLogs` leaves out the hidden columns, the key, and the timestamps.

## Considered Options

- Log only the fillable columns. Rejected: the audit then depends on which columns a form can fill, and changes that the app makes directly leave no record.

## Consequences

- The log is a deny list. Put each column that holds a secret, for example a token or a password, in `$hidden`, or its value goes into the activity log.
- Pivot changes, for example the permissions of a role, do not change a column. The Action that syncs them logs them.
