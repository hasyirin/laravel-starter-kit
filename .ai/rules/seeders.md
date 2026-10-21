---
paths:
  - 'app/Domain/**/Seeders/*.php'
---

# Seeders

## Extend the shared Seeder and keep seeders idempotent
Extend `App\Domain\Shared\Common\Seeders\Seeder`: return the rows from `data()`, write one row in `each()`, and use `before()` and `after()` for setup and cleanup. Seeding twice must change nothing, so match existing rows with `firstOrCreate()` or skip them.
