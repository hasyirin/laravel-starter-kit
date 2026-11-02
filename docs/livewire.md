# Livewire Components

New components extend `App\Livewire\Component`. It shows a toast when a workflow step fails (see [Workflows](workflows.md)), gives the ability helpers of `App\Livewire\Concerns\InteractsWithAbilities`, and makes the component refreshable.

## Refresh

Every component listens for the `refresh` event and renders again when it comes. The trait `App\Livewire\Concerns\Refreshable` adds the listener. Send the event to one component class, so that the other components on the page do not render again:

```php
$this->dispatch('refresh')->to(Documents::class);
```

A `refresh` event without `to()` renders every component on the page again. Do not name a method of your own `refresh()`.

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

The view gets `$disabled` with the result of `isDisabled()`. Pass it on to the inputs, for example `<x-input wire:model="form.name" :$disabled />`.

A disabled input in the browser does not stop a request. Start each action that changes data with `ensureEnabled()`. It answers 403 when the component is disabled:

```php
public function save(): void
{
    $this->ensureEnabled();

    // ...
}
```

### Disable One Part

`isDisabled()` and `ensureEnabled()` take an optional part, such as `roles`. Without an override, a part follows the whole component. To lock one part by a rule, check the part in the override, and keep `$this->disabled` in the check, so that a disabled component also disables every part. The typed abilities of the policies are a good rule, for example the `$type` of `App\Domain\User\Policies\UserPolicy::update()`:

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
