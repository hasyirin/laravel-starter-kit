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

A form object holds the input of one record. It extends `App\Livewire\Form` and lives in a folder under `app/Livewire/Forms`, such as `app/Livewire/Forms/Admin`. One form serves both the create page and the edit page. `App\Livewire\Forms\Admin\UserForm` is the example.

A form has two main methods:

- `set()` fills the form from a record. Call it in `mount()`, and again after a save.
- `save()` validates the input, calls the Action class, and returns the record. When the form has no record, `save()` creates one.

The form does not write models. The Action class does the write and opens the transaction. So the component only authorizes the user, calls `save()`, and shows the result:

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

## Disable

`App\Livewire\Concerns\CanBeDisabled` gives a component a `disabled` prop. The parent passes it, and the component follows when the parent changes it:

```blade
<livewire:documents :disabled="! $canEdit" />
```

The prop is reactive, so the browser cannot change it, and the component cannot set it itself. To disable a component by its own rule, override `isDisabled()`:

```php
public function isDisabled(?string $part = null): bool
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

`isDisabled()` and `ensureEnabled()` take an optional part, such as `roles`. Without an override, a part follows the whole component. To lock one part by a rule, check the part in the override. Keep `$this->disabled` in the check, so that a disabled component also disables every part. The typed abilities of the policies are a good rule, for example the `$type` of `App\Domain\User\Policies\UserPolicy::update()`:

```php
public function isDisabled(?string $part = null): bool
{
    return $this->disabled
        || ($part !== null && $this->cannot('update', [$this->user, $part]));
}
```

The view asks for the part, and the action checks the same part when the user changed it:

```blade
<x-checkbox wire:model="form.roles" :options="$this->roles" :disabled="$this->isDisabled('roles')" />
```

```php
if ($this->form->roles !== $this->originalRoles) {
    $this->ensureEnabled('roles');
}
```

`App\Livewire\Components\FileUpload` uses the trait. A disabled file upload ignores uploads, removes, and moves without an error.

## Modals

`App\Livewire\Modal` and `App\Livewire\RecordModal` are the base classes for dialogs. See [Modal](components/modal.md).
