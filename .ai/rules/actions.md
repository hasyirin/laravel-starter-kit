---
paths:
  - 'app/Domain/**/Actions/**'
---

# Actions

## Write one Action per operation
Put each operation that changes data or applies a domain rule in its own class in `app/Domain/<Context>/Actions/`, named after the operation, for example `UpdateProfile`. Use the `App\Domain\Shared\Common\Concerns\Actions\Actionable` trait, give the class one public `execute()` method with typed parameters and a return type, and take its dependencies through the constructor. Every Action uses the trait, and `ActionableTest` fails when one does not. Fortify actions keep the method names of their Fortify contracts instead of `execute()`.

## Get an Action from the container
Inject the Action where the caller supports it, such as a component method or a job's `handle()`. Elsewhere, call `UpdateProfile::make()`, which resolves the Action and its dependencies from the container, so a test can swap it. Do not create it with `new`.

## Keep the interface out of Actions
An Action takes models and plain values, not the request. It never shows a toast, flashes the session, or redirects. Its caller validates the input and authorizes the user first, so components, jobs, and commands can share the Action.

## Let the transition class hold a state step's work
A state step needs no Action: its transition class holds the work, with the checks in `reason()` and the changes in `apply()`. Pass input to the step as transition arguments, and give each extra constructor parameter a default, because `allowedFor()` builds the transition without input. An Action calls `$model->state->transitionTo()` only when the step is one part of a bigger operation.
