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
Tables for models that extend the base `Model` need `$table->softDeletesTz()`. On soft-deleted tables, enforce uniqueness with a partial index, not `->unique()`: `DB::statement('create unique index <table>_<columns>_unique on <table> (<columns>) where deleted_at is null')`.

## Keep the time zone in timestamp columns
Use `timestampTz()`, `timestampsTz()`, `nullableTimestampsTz()`, `softDeletesTz()`, and `dateTimeTz()`, never the variants without `Tz`. PostgreSQL then stores each value as a moment, and the `pgsql` connection reads and writes values in `app.timezone`. A time of day without a date, such as an opening time, can stay `time()`. A test fails on a migration that uses a variant without `Tz`.
