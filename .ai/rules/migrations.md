---
paths:
  - 'database/migrations/**'
---

# Migrations

## Pass the model's table to constrained()
Always pass the referenced table from the model: `$table->foreignIdFor(Organization::class)->constrained(new Organization()->getTable())`. Do not call `constrained()` without an argument. Use the same form with a custom column, for example `$table->foreignId('owner_id')->constrained(new User()->getTable())`, so that every foreign key names its table the same way.
