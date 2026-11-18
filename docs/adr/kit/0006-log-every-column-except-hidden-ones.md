# Log Every Column Except the Hidden Ones

The audit log records the changes to every column of a model, not only the fillable ones. Columns that the app sets directly, for example `email_verified_at` and `two_factor_confirmed_at`, change who can sign in or what a user can do, so the audit must show them. `InteractsWithAuditLog` leaves out the hidden columns, the key, the timestamps, the unique id columns of `uniqueIds()`, and the columns that a model names in `auditExcludedColumns()`. The timestamps include `deleted_at`.

## Considered Options

- Log only the fillable columns. Rejected: the audit then depends on which columns a form can fill, and changes that the app makes directly leave no record.
- Put a column that means nothing to a reader of the log, such as the link to a parent record, in `$hidden`. Rejected: `$hidden` also leaves it out of arrays and JSON.

## Consequences

- The log is a deny list. Put each column that holds a secret, for example a token or a password, in `$hidden`, or its value goes into the audit log.
- If a column holds no secret but means nothing to a reader of the log, put it in `auditExcludedColumns()`. An example is the link to a parent record. Keep `$hidden` for secrets.
- Pivot changes, for example the permissions of a role, do not change a column. The Action class that syncs them logs them.
