<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization;

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Positions extends Component
{
    #[Locked]
    public Organization $organization;

    #[Locked]
    public ?Department $department = null;

    public function mount(): void
    {
        $this->authorize('view', $this->organization);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.positions');
    }
}
