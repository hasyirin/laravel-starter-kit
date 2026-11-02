<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User;

use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Show extends Page
{
    public function render(): View
    {
        return view('livewire.pages.admin.controls.user.index');
    }
}
