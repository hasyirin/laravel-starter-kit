<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\Role;

use App\Domain\Shared\Permission\Models\Role;
use App\Livewire\Pages\Admin\Controls\Role\Concerns\InteractsWithRoleForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Create extends Page
{
    use InteractsWithRoleForm;

    public function mount(): void
    {
        $this->authorize('create', Role::class);
    }

    public function save(): void
    {
        $this->authorize('create', Role::class);

        $role = $this->form->save();

        $this->notifySaved((string) __('permission/roles.role'), created: true);
        $this->redirect(route('admin.controls.roles.show', $role), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.role.create')->title(__('permission/roles.create.title'));
    }
}
