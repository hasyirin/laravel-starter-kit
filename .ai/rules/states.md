---
paths:
  - 'app/Domain/**/States/**'
---

# States

## Keep a state column in one folder
Give each state column its own folder under `States`. Put the abstract base state and every concrete state in that folder, because Spatie finds the states by folder. The base state extends `App\Domain\Shared\Common\States\State`, and the model uses `Spatie\ModelStates\HasStates` and casts the column to the base state.

## Name each state in snake case
Set `public static string $name` on each concrete state, for example `site_visit`. Spatie stores this name, and its docs do not allow hyphens.

## Declare transitions in configure()
Set the default state with `#[DefaultState(...)]`. List the transitions in the base state's `protected static function configure(StateConfig $config): StateConfig`, one `allowTransition()` call per step, with all its from-states in one array. Do not use the `#[AllowTransition]` attribute. Do not override `configure()` in a concrete state, or `config()` anywhere: `config()` is final, and it builds one config for the whole column, from the first state that asks for it.

## Keep configure() free of runtime data
`configure()` runs once per PHP process, and the kit keeps its result: across requests in an Octane worker, and across jobs in a queue worker. So list fixed rules only. Do not read the user, the request, the config, or the database in it. Put a check that depends on the record or the user in a transition's `reason()` or in the policy.
