<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\Role;

use App\Domain\Shared\Permission\Models\Role;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Audit extends Component
{
    #[Locked]
    public Role $role;

    public function mount(): void
    {
        $this->authorize('view', [$this->role, 'audit-log']);
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.role.audit');
    }
}
