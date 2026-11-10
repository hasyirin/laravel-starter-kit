<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization;

use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Letterheads extends Component
{
    #[Locked]
    public Organization $organization;

    public function mount(): void
    {
        $this->authorize('view', $this->organization);
        $this->authorize('viewAny', Letterhead::class);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.letterheads');
    }
}
