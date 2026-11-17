---
paths:
  - 'app/Domain/**/Actions/**'
---

# Action classes

## Write one Action class per operation
Put each operation that changes data or applies a domain rule in its own class in `app/Domain/<Context>/Actions/`, or in a group folder below it. Name the class after the operation, for example `UpdateUserInformation`. Use the `App\Domain\Shared\Common\Concerns\Actions\Actionable` trait. Give the class one public `execute()` method with typed parameters and a return type. Every Action class uses the trait, and `ActionableTest` fails when one does not. An Action class that implements a Fortify contract, such as `RegisterUser`, keeps the method name of the contract instead of `execute()`.

## Group Action classes only when a context needs it
Keep `Actions/` flat while a context has few Action classes. Add group folders only when the context has two or more clear groups, and then put every Action class in a group. Name a group after a topic, such as `Registration`, `Security`, `Settings`, or `Impersonation`. Put an operation in `Management/` when a user does it to records of other users, such as `Management\CreateUser`. The area that calls the Action class does not decide the group, so do not name a group `Internal` or `System`. A group can hold one Action class. Add a second level only when a group is crowded itself. Do not make a folder for a package, such as `Fortify/`. Give each Action class a name that is unique in its context, for example `Management\DeleteOtherUser` and `Settings\DeleteUser`. Use `User` before the object in a name only when the operation is on another user, such as `VerifyUserEmail`. Put the test of an Action class in the same group folder under `tests/Feature/Domain/<Context>/Actions/`.

## Pass values that travel together as one value object
Give an Action class typed arguments. If a call has more than 4 arguments, name them. Make a `final readonly` class in the context's `Support/` folder only if one group of values goes through 2 or more Action classes, or a caller other than a form builds it. `Placement` is the example. Do not make a data class for each form.

## Get an Action class from the container
Call an Action class through `UpdateUserInformation::make()` in every caller: a component, a job, a command, or another Action class. `make()` resolves the Action class from the container, so a test can swap it. Never take an Action class as a parameter, and never create it with `new`.

## Make an Action class safe inside an outer transaction
An Action class can open its own transaction. It can also run inside the transaction of a caller: a form, a component, or another Action class. Laravel then makes a savepoint, and nothing commits until the outer transaction commits. So undo work outside the database in `afterRollBack()` inside the transaction. Media files need no code of their own. The kit's `MediaObserver` removes the files of a new media on a rollback. It deletes the files of a deleted media only after the commit. A rollback resets the database, not the models in memory. Most callers stop after a failure, so a changed model needs no care. A model needs care only when its caller keeps using it after a rollback: it catches the exception and stays on the page, it goes on with the next record in a loop, or it tries the transaction again. Then that caller puts the model back with `ModelSnapshot::restoreOnRollBack()` as the first step in the transaction, or it loads the model again with `fresh()`. An Action class does this only for such a caller that it knows. The `Transition` base class does it, because a component stays on the page after a failed step. A form needs no such Action class: `transaction()` of `App\Livewire\Form` takes the snapshot. Do not add a snapshot to every Action class. A catch block around the own transaction does not run when a later step of the caller fails. Start mail, jobs, and other effects with `afterCommit()` or `ShouldQueueAfterCommit`, so they wait for the outer commit. Do not catch an exception inside a transaction to continue.

## Keep the interface out of Action classes
An Action class takes models and plain values, not the request. It never shows a toast, flashes the session, or redirects. Its caller validates the input and authorizes the user first, so components, jobs, and commands can share the Action class.

## Let the transition class hold a state step's work
A state step needs no Action class: its transition class holds the work, with the checks in `reason()` and the changes in `apply()`. Pass input to the step as arguments of its transition class. Give each extra constructor parameter a default, because `allowedFor()` builds the transition class without input. An Action class calls `$model->state->transitionTo()` only when the step is one part of a bigger operation.

## Never keep a hidden value that may be stale
A form can hide a part of a record, such as the districts or the location of an address. The values then do not hold that part, and the Action class keeps its saved value. Keep it only while it still fits the values that the user changed. If a rule can check it, check it: `SaveAddress` clears a district that is not in the new state. If nothing can check it, clear it when a value it depends on changes: `SaveAddress` clears a hidden location when a line, the postcode, or the city changes. A missing value shows, so a page that shows the part asks for it again. A stale value shows as correct.
