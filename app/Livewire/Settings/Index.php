<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Index extends Page
{
    public function render(): View
    {
        return view('livewire.settings.index')->title(__('user/settings.heading'));
    }
}
