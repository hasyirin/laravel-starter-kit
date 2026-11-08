<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization;

use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Index extends Page
{
    public function mount(): void
    {
        $this->authorize('viewAny', Organization::class);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.index')->title(__('organization/organizations.title'));
    }
}
