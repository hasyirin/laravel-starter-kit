<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System\ApiClient;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Livewire\Forms\System\ApiClientForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Create extends Page
{
    public ApiClientForm $form;

    public function mount(): void
    {
        $this->authorize('create', ApiClient::class);
    }

    public function save(): void
    {
        $this->authorize('create', ApiClient::class);

        $apiClient = $this->form->save();

        $this->notifySaved((string) __('api/api-clients.api_client'), created: true);
        $this->redirect(route('system.api-clients.show', $apiClient), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.system.api-client.create')->title(__('api/api-clients.create.title'));
    }
}
