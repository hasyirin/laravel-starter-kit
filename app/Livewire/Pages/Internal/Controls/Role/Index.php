<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Role;

use App\Domain\Shared\Permission\Models\Role;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Index extends Page
{
    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.role.index')->title(__('permission/roles.title'));
    }
}
