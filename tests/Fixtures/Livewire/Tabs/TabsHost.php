<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\Tabs;

use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use Illuminate\Contracts\View\View;

class TabsHost extends Component
{
    use InteractsWithTabs;

    /**
     * @var list<string>
     */
    public array $hiddenTabs = [];

    /**
     * @var list<string>
     */
    public array $disabledTabs = ['movements'];

    /**
     * @var list<string>
     */
    public array $lazyTabs = [];

    /**
     * @var array<string, mixed>
     */
    public array $contentParams = [];

    public string $orientation = 'horizontal';

    /**
     * @param  list<string>  $hiddenTabs
     * @param  list<string>  $disabledTabs
     * @param  list<string>  $lazyTabs
     * @param  array<string, mixed>  $contentParams
     */
    public function mount(array $hiddenTabs = [], array $disabledTabs = ['movements'], array $lazyTabs = [], array $contentParams = [], string $orientation = 'horizontal'): void
    {
        $this->hiddenTabs = $hiddenTabs;
        $this->disabledTabs = $disabledTabs;
        $this->lazyTabs = $lazyTabs;
        $this->contentParams = $contentParams;
        $this->orientation = $orientation;
    }

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            $this->flaggedTab('owner')->content(OwnerPanel::class)->params(['source' => 'tab', 'heading' => 'Owner']),
            $this->flaggedTab('documents')->content(DocumentsPanel::class)->keepAlive(),
            $this->flaggedTab('notes')->content(fn (): View => view()->file(__DIR__.'/notes.blade.php'))->params(['source' => 'tab', 'heading' => 'Notes']),
            $this->flaggedTab('movements')->content(OwnerPanel::class),
        ];
    }

    public function continueToDocuments(): void
    {
        if ($this->currentTab?->slug() === 'owner') {
            $this->switchTab('documents');
        }
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <x-tabs.content :panels="$tabPanels" :current-tab="$currentTab" :orientation="$orientation" :params="$contentParams" />
            </div>
            BLADE;
    }

    private function flaggedTab(string $slug): Tab
    {
        return Tab::make($slug)
            ->lazy(in_array($slug, $this->lazyTabs, true))
            ->hidden(fn (): bool => in_array($slug, $this->hiddenTabs, true))
            ->disabled(fn (): bool => in_array($slug, $this->disabledTabs, true));
    }
}
