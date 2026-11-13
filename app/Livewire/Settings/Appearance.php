<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Appearance extends Page
{
    public function render(): View
    {
        return view('livewire.settings.appearance')->title(__('user/settings.appearance.title'));
    }
}
