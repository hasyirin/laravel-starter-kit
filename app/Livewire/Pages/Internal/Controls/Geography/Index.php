<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography;

use App\Domain\Shared\Geography\Models\Country;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Index extends Page
{
    public function mount(): void
    {
        $this->authorize('viewAny', Country::class);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.index')->title(__('geography/geography.title'));
    }
}
