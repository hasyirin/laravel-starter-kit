# Livewire Components

New components extend `App\Livewire\Component`. It shows a toast when a workflow step fails (see [Workflows](workflows.md)), gives the ability helpers of `App\Livewire\Concerns\InteractsWithAbilities`, shows the saved toast of a record, and makes the component refreshable.

## Saved Toast

After you save a record, call `notifySaved()` with the name of the record. Pass `created: true` for a new record. The toast shows "User created." or "User updated." from `common/records.created` and `common/records.updated`:

```php
$this->notifySaved(__('user/users.user'), created: false);
```

For any other message, call `Flux::toast()` with a sentence of its own.

## Refresh

Every component listens for the `refresh` event and renders again when it comes. The trait `App\Livewire\Concerns\Refreshable` adds the listener. Send the event to one component class, so that the other components on the page do not render again:

```php
$this->dispatch('refresh')->to(Documents::class);
```

A `refresh` event without `to()` renders every component on the page again. Do not name a method of your own `refresh()`.

## Forms

A form object holds the input of one record. It extends `App\Livewire\Form` and lives in a folder under `app/Livewire/Forms`, such as `app/Livewire/Forms/Internal`. One form serves both the create page and the edit page. `App\Livewire\Forms\Internal\UserForm` is the example.

A form has two main methods:

- `set()` fills the form from a record. Call it in `mount()`, and again after a save.
- `save()` validates the input, calls the Action class, and returns the record. When the form has no record, `save()` creates one.

A form for a child record takes the parent once. An edit page passes it as the first argument of `set()`, as the Record Modal section of [Modal](components/modal.md) shows. A create page passes it to `save()`, because the page saves the parent first, in the same request. [Address](components/address.md) shows both. Livewire rebuilds a new record as a fully empty model on the next request. So the Action class sets the parent again on every save. It also sets every other value that the user does not enter, such as a type.

The form does not write models itself. The Action classes do the writes. So the component only authorizes the user, calls `save()`, and shows the result:

```php
public function mount(User $user): void
{
    $this->authorize('view', $user);

    $this->user = $user;
    $this->form->set($user);
}

public function save(): void
{
    $this->authorize('update', $this->user);

    $this->user = $this->form->save();
    $this->form->set($this->user);

    $this->notifySaved(__('user/users.user'), created: false);
}
```

Mark the record and every other property that the user does not type with `#[Locked]`. A browser can change any public property that is not locked. A flag that changes the rules, such as `required`, must be locked too.

When the user can change only some parts of a record, check each part with the policy in `save()`. For example, `UserForm` sends the roles to the Action class only when the user can `update` the `roles` of that user. A disabled input in the browser does not stop a request, so the check must be on the server.

## Transactions

A form runs the Action classes of `save()` in `$this->transaction()` from `App\Livewire\Form`. It opens a database transaction around the calls. Then all writes commit or roll back together. Without it, a failure in the second Action class leaves the writes of the first one in the database. A form that calls one Action class uses it too, so every form behaves the same way. A component that calls `save()` of more than one form wraps the calls in `DB::transaction()`. `tests/Fixtures/Livewire/OrganizationDepartmentForm.php` is the full example:

```php
protected function errorKey(string $key): string
{
    return "department_$key";
}

public function save(): Department
{
    $this->validate();

    return $this->transaction(function (): Department {
        SaveOrganization::make()->execute($this->organization, $this->organization->code, $this->name);

        return SaveDepartment::make()->execute($this->organization, new Department, $this->department_code, $this->department_name);
    });
}
```

`transaction()` returns the value of the closure, with its type. Use `$this->mapErrors()` instead only when the work must not run in a transaction. `ComposeLetterForm::print()` is the example: it renders a PDF in Chrome, which can take seconds. `mapErrors()` maps the error keys the same way, without a transaction.

### Nested transactions

Laravel counts the transaction levels of each connection. The first `transaction()` starts the database transaction. Each inner `transaction()`, such as one in an Action class, makes a savepoint. An inner commit writes nothing. The database commits only when the outer transaction commits.

### Exceptions

If an Action class throws, Laravel rolls back to its savepoint and throws the exception again. The exception leaves the closure of the outer transaction, so the outer transaction rolls back too. Do not catch an exception inside the transaction to continue. Catch it outside, after the rollback.

Livewire turns a `Illuminate\Validation\ValidationException` into errors of the same request. An Action class gives the errors its own keys, such as code. The form shows errors under its own keys, such as form.department_code. So `transaction()` catches the exception outside the transaction, after the rollback, and throws it again with new keys. It prefixes each key with the property name of the form, from `getPropertyName()`. A form that has a different name for a field overrides `errorKey(string $key)`, as the example and `LetterForm` do. The default returns the key as it is. Any other exception goes on as before. For example, the base component shows a toast for a failed workflow step.

Never catch this exception in a form, and never write "form." as fixed text. A component can hold the form under another name, such as `public UserForm $userForm`. A fixed "form." then puts the error on a field that does not exist. The error shows nowhere, and the save fails without a message.

Livewire creates a form only for a property of the component. A form inside another form must be created by hand, with the full path as its property name, such as `new AddressForm($this->getComponent(), $this->getPropertyName().'.address')`. Livewire then keeps that path on each later request.

### Locks

A row lock from `lockForUpdate()` stays until the outer transaction ends, also when an inner Action class took it. So keep the transaction short. Do not call an HTTP service or render a file in it. Lock rows in the same order in every operation, so two requests do not deadlock. Laravel retries nothing by default. It retries a deadlock only when you pass attempts to the outer `transaction()`. An inner transaction throws a `Illuminate\Database\DeadlockException` at once, without a retry. SQLite ignores the lock.

### Work after the commit

Laravel runs an `afterCommit()` callback only when the outer transaction commits. A rollback drops it. These also wait for the outer commit:

- an event that implements `Illuminate\Contracts\Events\ShouldDispatchAfterCommit`, such as `StateChanged`
- a job or a queued notification that implements `Illuminate\Contracts\Queue\ShouldQueueAfterCommit`, such as every notification that extends `App\Domain\Shared\Message\Notifications\Notification`

So does a job that you dispatch with `afterCommit()`. Any other queued job goes to the queue at once, because `config/queue.php` does not wait for the commit. A worker can then run it before the commit, or after a rollback. Model events run at once, inside the transaction. So does an observer, unless it implements `Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit`.

### Models after a rollback

A rollback changes only the database. The models in memory keep the values that the Action classes set. A model that an Action class created keeps its id and `exists`, but its row is gone. Livewire loads each model property again from the database on the next request. But the view of the same request shows the unsaved values. And Livewire keeps a model that exists by its key, so a new model that rolled back fails to load on the next request. So `transaction()` calls `ModelSnapshot::restoreOnRollBack()` for each model property of the form, as its first step. On a rollback, it puts each record back as it was before the transaction. When several snapshots of one record roll back together, the earliest one wins. In a form:

- If the form keeps another model after the save, such as a related model it shows, call `ModelSnapshot::restoreOnRollBack()` for it as the first step in the closure. The helper restores only the model you pass. After a failed save, do not save a model that an Action class changed until you load it again.
- Make a new record inside the closure. Put it on a form property only after `transaction()` returns.
- Keep the input properties as they are. The user sees the input and the errors, and can try again.

Work outside the database, such as a stored file, does not roll back. An Action class undoes it in `afterRollBack()`. See the Transactions section of [Action Classes](actions.md).

## Disable

`App\Livewire\Concerns\CanBeDisabled` gives a component a `disabled` prop. The parent passes it, and the component follows when the parent changes it:

```blade
<livewire:documents :disabled="! $canEdit" />
```

The prop is reactive, so the browser cannot change it, and the component cannot set it itself. To disable a component by its own rule, override `isDisabled()`:

```php
public function isDisabled(): bool
{
    return $this->disabled || $this->project->isArchived();
}
```

The view gets `$disabled` with the result of `isDisabled()`. Pass it once to the card or the card group that holds the inputs, for example `<x-card.group form wire:submit="save" :$disabled>`, and every kit input inside follows. See [Disabled](components/card.md#disabled). An input outside a card takes it directly: `<x-input wire:model="form.name" :$disabled />`.

A disabled input in the browser does not stop a request. Start each action that changes data with `ensureEnabled()`. It answers 403 when the component is disabled:

```php
public function save(): void
{
    $this->ensureEnabled();

    // ...
}
```

### Disable One Part

`isDisabled()` is for the whole component. To lock one field by its own rule, such as a value that is fixed after creation, give the form an `editable(string $part)` method. The typed abilities of the policies are a good rule, for example the `$type` of `App\Domain\User\Policies\UserPolicy::update()`:

```php
public function editable(string $part): bool
{
    return $this->can('update', [$this->user, $part]);
}
```

The component wraps it, so that a disabled component also locks every field:

```php
public function editable(string $part): bool
{
    return ! $this->isDisabled() && $this->form->editable($part);
}
```

The view asks the component. Do not add `$disabled ||`, because `editable()` already includes it:

```blade
<x-checkbox wire:model="form.roles" :options="$this->roles" :disabled="! $this->editable('roles')" />
```

The form's `save()` checks the same part, because a browser can send any value. It ignores the value of a locked field, as `UserForm` does with the roles. An action that changes only one part answers 403 instead: `abort_unless($this->editable('roles'), 403);`.

`App\Livewire\Components\FileUpload` uses the trait. A disabled file upload ignores uploads, removes, and moves without an error.

## Modals

`App\Livewire\Modal` and `App\Livewire\RecordModal` are the base classes for dialogs. See [Modal](components/modal.md).
