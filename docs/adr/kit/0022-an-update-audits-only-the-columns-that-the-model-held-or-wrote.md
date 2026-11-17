# An Update Audits Only the Columns That the Model Held or Wrote

The package spatie/laravel-activitylog takes the old values of an update from the columns that the model holds. It takes the new values from the database row. A column that the model never held got the old value null. Examples are a database default after a create, and a column outside a partial select. So the audit entry of an update showed changes that did not happen. Now the audit entry of an update lists a column only when the model held its old value or the update wrote it. `InteractsWithAuditLog::buildChanges()` applies this rule.

## Considered Options

- **Read the real old value with one more query**: rejected. In `updating`, the kit could read each written column that the model did not hold. The entry would then show the real old value in place of no old value. But this adds a listener and a query for a rare case, because the kit's own models repeat their database defaults in `$attributes`. It also misses a column that an observer writes, because an observer runs after the kit's listener.
- **Find the written columns during `updating`**: rejected. An observer's `updating` method runs after the trait's listeners. A column that the observer writes would drop out of the entry. So the kit reads the written columns from `getChanges()` when the `updated` event fires.
- **Filter the entry in a custom action of the package, or in a callback before it logs**: rejected. Both run after the package checks for an empty entry. An update with only false changes would then write an empty entry.
- **Refresh the model after each create**: rejected. It costs one more query for each insert, and a partial select still logs false changes.
- **Ask each model to repeat its database defaults in `$attributes`**: rejected as the fix. It moves the work to each model of each app, and it does not cover a partial select.

## Consequences

- A column that the update wrote, but that the model did not hold, shows no old value, even when the row held one. For example, create a record and then set a flag to true. The entry shows no old value, although the row held false. If the update writes the value that the row already held, the entry still shows it as a change.
- A column that the database changes without a write, such as a generated column, can drop out. This happens when the model did not hold the column, for example right after a create. When the model held the column, the entry keeps its change.
- A model that reloads a column after each save, with Laravel's `$refreshes`, can log that column with no old value although it did not change.
- The trait overrides the protected `buildChanges()` method of the package and reads its `oldAttributes` property. An update of the package can break this. The tests in `tests/Feature/Domain/Shared/Common/Concerns/Models/InteractsWithAuditLogTest.php` then fail.
- This refines kit ADR-0006. The audit log still covers every column except the hidden ones, the key, and the timestamps. But the audit entry of an update leaves out a column when the kit cannot know that it changed.
- Audit entries that were written before this decision keep their false changes.
