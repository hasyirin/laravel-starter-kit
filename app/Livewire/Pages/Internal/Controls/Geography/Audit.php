<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography;

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Audit extends Component
{
    #[Locked]
    public Country|State|District|PostOffice $record;

    public function mount(): void
    {
        $this->authorize('view', [$this->record, 'audit-log']);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.audit');
    }
}
