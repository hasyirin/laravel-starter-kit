---
paths:
  - 'app/Domain/**/States/**'
---

# States

## Keep a state column in one folder
Give each state column its own folder under `States`. Put the abstract base state and every concrete state in that folder, because Spatie finds the states by folder. The base state extends `App\Domain\Shared\Common\States\State`, and the model uses `Spatie\ModelStates\HasStates` and casts the column to the base state.

## Name each state in snake case
Set `public static string $name` on each concrete state, for example `site_visit`. Spatie stores this name, and its docs do not allow hyphens.

## Declare transitions in config()
Set the default state with `#[DefaultState(...)]`. List the transitions in the base state's `config()`, one `allowTransition()` call per step, with all its from-states in one array. Do not use the `#[AllowTransition]` attribute.
