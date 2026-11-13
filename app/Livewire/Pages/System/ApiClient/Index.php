<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System\ApiClient;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Index extends Page
{
    public function mount(): void
    {
        $this->authorize('viewAny', ApiClient::class);
    }

    public function render(): View
    {
        return view('livewire.pages.system.api-client.index')->title(__('api/api-clients.title'));
    }
}
