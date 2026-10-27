<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Shared;

use App\Livewire\Component;
use Illuminate\View\View;

abstract class Page extends Component
{
    public function rendering(View $view): View
    {
        return $view->layout(match (true) {
            request()->routeIs('admin.*') => 'layouts.admin',
            request()->routeIs('internal.*') => 'layouts.internal',
            in_array('auth', request()->route()?->gatherMiddleware() ?? [], true) => 'layouts.app',
            default => 'layouts.guest',
        });
    }
}
