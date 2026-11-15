<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System\ApiClient;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Audit extends Component
{
    #[Locked]
    public ApiClient $apiClient;

    public function mount(): void
    {
        $this->authorize('view', [$this->apiClient, 'audit-log']);
    }

    public function render(): View
    {
        return view('livewire.pages.system.api-client.audit');
    }
}
