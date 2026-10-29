<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Local\Examples;

use App\Livewire\Component;
use Illuminate\Contracts\View\View;

class TabPanel extends Component
{
    public string $label = '';

    public string $value = '';

    public function render(): View
    {
        return view('livewire.pages.local.examples.tab-panel');
    }
}
