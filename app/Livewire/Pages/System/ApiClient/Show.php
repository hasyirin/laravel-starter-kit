<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System\ApiClient;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Forms\System\ApiClientForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Show extends Page
{
    use CanBeDisabled;

    #[Locked]
    public ApiClient $apiClient;

    public ApiClientForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->apiClient);

        $this->form->set($this->apiClient);
    }

    public function isDisabled(): bool
    {
        return $this->disabled === true || $this->cannot('update', $this->apiClient);
    }

    public function save(): void
    {
        $this->authorize('update', $this->apiClient);
        $this->ensureEnabled();

        $this->apiClient = $this->form->save();
        $this->form->set($this->apiClient);

        $this->notifySaved((string) __('api/api-clients.api_client'), created: false);
    }

    public function render(): View
    {
        return view('livewire.pages.system.api-client.show')->title($this->apiClient->name);
    }
}
