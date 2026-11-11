<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography;

use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Subdistricts extends Component
{
    #[Locked]
    public ?District $district = null;

    public function mount(): void
    {
        if ($this->district instanceof District) {
            $this->authorize('view', $this->district);

            return;
        }

        $this->authorize('viewAny', Subdistrict::class);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.subdistricts');
    }
}
