<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography;

use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Postcodes extends Component
{
    #[Locked]
    public ?PostOffice $postOffice = null;

    public function mount(): void
    {
        if ($this->postOffice instanceof PostOffice) {
            $this->authorize('view', $this->postOffice);

            return;
        }

        $this->authorize('viewAny', Postcode::class);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.postcodes');
    }
}
