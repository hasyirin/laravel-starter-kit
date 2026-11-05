<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Role;

use App\Domain\Shared\Permission\Models\Role;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Pages\Internal\Controls\Role\Concerns\InteractsWithRoleForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Show extends Page
{
    use CanBeDisabled;
    use InteractsWithRoleForm;

    #[Locked]
    public Role $role;

    public function mount(): void
    {
        $this->authorize('view', $this->role);

        $this->form->set($this->role);
    }

    public function isDisabled(?string $part = null): bool
    {
        return $this->disabled === true
            || $this->cannot('update', $this->role)
            || ($this->role->checkPermissionTo($this->bypassPermission()) && ! $this->actorBypasses());
    }

    public function editable(string $part): bool
    {
        return ! $this->isDisabled() && $this->form->editable($part);
    }

    public function save(): void
    {
        $this->authorize('update', $this->role);
        $this->ensureEnabled();

        $this->role = $this->form->save();
        $this->form->set($this->role);

        $this->notifySaved((string) __('permission/roles.role'), created: false);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.role.show')->title($this->role->label);
    }
}
