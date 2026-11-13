<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System;

use App\Domain\Shared\Api\Models\ApiCall;
use App\Livewire\Modal;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class ApiCallModal extends Modal
{
    #[Locked]
    public ?ApiCall $apiCall = null;

    public function mount(): void
    {
        $this->authorize('viewAny', ApiCall::class);
    }

    #[On('show')]
    public function show(?int $id = null): void
    {
        abort_if($id === null, 404);

        $apiCall = ApiCall::query()->with('apiToken')->findOrFail($id);

        $this->authorize('view', $apiCall);

        $this->apiCall = $apiCall;

        parent::show();
    }

    public function render(): View
    {
        return view('livewire.pages.system.api-call-modal');
    }
}
