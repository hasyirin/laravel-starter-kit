<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System\ApiClient;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Tabs extends Component
{
    use InteractsWithTabs;

    #[Locked]
    public ApiClient $apiClient;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('tokens')
                ->label((string) __('api/api-clients.tabs.tokens'))
                ->content(Tokens::class)
                ->params(['apiClient' => $this->apiClient]),
            Tab::make('audit')
                ->label((string) __('api/api-clients.tabs.audit'))
                ->content(Audit::class)
                ->params(['apiClient' => $this->apiClient])
                ->visible(fn (): bool => $this->can('view', [$this->apiClient, 'audit-log'])),
        ];
    }

    protected function rendersWithoutTabs(): bool
    {
        return true;
    }

    public function render(): View
    {
        return view('livewire.pages.system.api-client.tabs');
    }
}
