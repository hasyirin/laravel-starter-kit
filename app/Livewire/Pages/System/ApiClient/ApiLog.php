<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System\ApiClient;

use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class ApiLog extends Component
{
    #[Locked]
    public ApiClient $apiClient;

    public function mount(): void
    {
        $this->authorize('viewAny', ApiCall::class);
    }

    public function render(): View
    {
        return view('livewire.pages.system.api-client.api-log');
    }
}
