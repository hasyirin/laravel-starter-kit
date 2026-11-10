---
paths:
  - 'app/Domain/**/Actions/**'
---

# Action classes

## Write one Action class per operation
Put each operation that changes data or applies a domain rule in its own class in `app/Domain/<Context>/Actions/`. Name the class after the operation, for example `UpdateUserInformation`. Use the `App\Domain\Shared\Common\Concerns\Actions\Actionable` trait. Give the class one public `execute()` method with typed parameters and a return type. Every Action class uses the trait, and `ActionableTest` fails when one does not. Fortify's Action classes keep the method names of their Fortify contracts instead of `execute()`.

## Get an Action class from the container
Call an Action class through `UpdateUserInformation::make()` in every caller: a component, a job, a command, or another Action class. `make()` resolves the Action class from the container, so a test can swap it. Never take an Action class as a parameter, and never create it with `new`.

## Keep the interface out of Action classes
An Action class takes models and plain values, not the request. It never shows a toast, flashes the session, or redirects. Its caller validates the input and authorizes the user first, so components, jobs, and commands can share the Action class.

## Let the transition class hold a state step's work
A state step needs no Action class: its transition class holds the work, with the checks in `reason()` and the changes in `apply()`. Pass input to the step as arguments of its transition class. Give each extra constructor parameter a default, because `allowedFor()` builds the transition class without input. An Action class calls `$model->state->transitionTo()` only when the step is one part of a bigger operation.

## Never keep a hidden value that may be stale
A form can hide a part of a record, such as the districts or the location of an address. The values then do not hold that part, and the Action class keeps its saved value. Keep it only while it still fits the values that the user changed. If a rule can check it, check it: `SaveAddress` clears a district that is not in the new state. If nothing can check it, clear it when a value it depends on changes: `SaveAddress` clears a hidden location when a line, the postcode, or the city changes. A missing value shows, so a page that shows the part asks for it again. A stale value shows as correct.
