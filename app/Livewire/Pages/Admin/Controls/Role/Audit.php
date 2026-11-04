<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\Role;

use App\Domain\Shared\Permission\Models\Role;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

class Audit extends Component
{
    #[Locked]
    public int $roleId;

    public function mount(): void
    {
        $this->authorize('view', [$this->role, 'activities']);
    }

    #[Computed]
    public function role(): Role
    {
        return Role::query()->findOrFail($this->roleId);
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.role.audit');
    }
}
