---
paths:
  - 'app/Domain/**/Actions/**'
---

# Actions

## Write one Action per operation
Put each operation that changes data or applies a domain rule in its own class in `app/Domain/<Context>/Actions/`. Name the class after the operation, for example `UpdateUserInformation`. Use the `App\Domain\Shared\Common\Concerns\Actions\Actionable` trait. Give the class one public `execute()` method with typed parameters and a return type. Every Action uses the trait, and `ActionableTest` fails when one does not. Fortify actions keep the method names of their Fortify contracts instead of `execute()`.

## Get an Action from the container
Call an Action through `UpdateUserInformation::make()` in every caller: a component, a job, a command, or another Action. `make()` resolves the Action from the container, so a test can swap it. Never take an Action as a parameter, and never create it with `new`.

## Keep the interface out of Actions
An Action takes models and plain values, not the request. It never shows a toast, flashes the session, or redirects. Its caller validates the input and authorizes the user first, so components, jobs, and commands can share the Action.

## Let the transition class hold a state step's work
A state step needs no Action: its transition class holds the work, with the checks in `reason()` and the changes in `apply()`. Pass input to the step as transition arguments. Give each extra constructor parameter a default, because `allowedFor()` builds the transition without input. An Action calls `$model->state->transitionTo()` only when the step is one part of a bigger operation.
