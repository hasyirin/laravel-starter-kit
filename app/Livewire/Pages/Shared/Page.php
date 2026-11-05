<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Shared;

use App\Http\Middleware\EnsureLocalEnvironment;
use App\Livewire\Component;
use Illuminate\View\View;

abstract class Page extends Component
{
    public function rendering(View $view): View
    {
        $middleware = request()->route()?->gatherMiddleware() ?? [];

        return $view->layout(match (true) {
            in_array(EnsureLocalEnvironment::class, $middleware, true) => 'layouts.local',
            request()->routeIs('admin.*') => 'layouts.admin',
            request()->routeIs('internal.*') => 'layouts.internal',
            in_array('auth', $middleware, true) => 'layouts.app',
            default => 'layouts.guest',
        });
    }
}
