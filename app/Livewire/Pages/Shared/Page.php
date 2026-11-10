<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Shared;

use App\Http\Enums\Area;
use App\Http\Middleware\EnsureLocalEnvironment;
use App\Livewire\Component;
use Illuminate\View\View;

abstract class Page extends Component
{
    public function rendering(View $view): View
    {
        $middleware = request()->route()?->gatherMiddleware() ?? [];

        if (in_array(EnsureLocalEnvironment::class, $middleware, true)) {
            return $view->layout('layouts.local');
        }

        return $view->layout('layouts.'.Area::current()->value);
    }
}
