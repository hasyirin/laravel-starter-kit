<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Audit extends Component
{
    #[Locked]
    public Organization|Department|Branch $record;

    public function mount(): void
    {
        $this->authorize('view', [$this->record, 'audit-log']);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.audit');
    }
}
