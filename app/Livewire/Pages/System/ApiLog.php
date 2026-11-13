<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System;

use App\Domain\Shared\Api\Models\ApiCall;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class ApiLog extends Page
{
    public function mount(): void
    {
        $this->authorize('viewAny', ApiCall::class);
    }

    public function render(): View
    {
        return view('livewire.pages.system.api-log')->title(__('navigation.api_log'));
    }
}
