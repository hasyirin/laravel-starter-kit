<?php

declare(strict_types=1);

namespace App\Livewire\Pages\App;

use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Dashboard extends Page
{
    public function render(): View
    {
        return view('livewire.pages.app.dashboard')->title(__('navigation.dashboard'));
    }
}
