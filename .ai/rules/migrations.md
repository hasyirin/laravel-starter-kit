---
paths:
  - 'database/migrations/**'
---

# Migrations

## Pass the model's table to constrained()
Always pass the referenced table from the model: `$table->foreignIdFor(Organization::class)->constrained(new Organization()->getTable())`. Do not call `constrained()` without an argument. Use the same form with a custom column, for example `$table->foreignId('owner_id')->constrained(new User()->getTable())`, so that every foreign key names its table the same way.

## Index every foreign key column
Add `->index()` to every foreign key column, before `constrained()`: `$table->foreignIdFor(Organization::class)->index()->constrained(new Organization()->getTable())`.

## Make unique indexes skip soft-deleted rows
Tables for models that extend the base `Model` need `$table->softDeletes()`. On soft-deleted tables, enforce uniqueness with a partial index, not `->unique()`: `DB::statement('create unique index <table>_<columns>_unique on <table> (<columns>) where deleted_at is null')`.
