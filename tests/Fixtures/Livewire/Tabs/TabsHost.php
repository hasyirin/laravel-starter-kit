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
     * @param  list<string>  $hiddenTabs
     * @param  list<string>  $disabledTabs
     */
    public function mount(array $hiddenTabs = [], array $disabledTabs = ['movements']): void
    {
        $this->hiddenTabs = $hiddenTabs;
        $this->disabledTabs = $disabledTabs;
    }

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            $this->flaggedTab('owner')->content(OwnerPanel::class),
            $this->flaggedTab('documents')->content(DocumentsPanel::class)->keepAlive(),
            $this->flaggedTab('notes')->content(fn (): View => view()->file(__DIR__.'/notes.blade.php')),
            $this->flaggedTab('movements')->content(OwnerPanel::class),
        ];
    }

    public function render(): string
    {
        return '<div></div>';
    }

    private function flaggedTab(string $slug): Tab
    {
        return Tab::make($slug)
            ->hidden(fn (): bool => in_array($slug, $this->hiddenTabs, true))
            ->disabled(fn (): bool => in_array($slug, $this->disabledTabs, true));
    }
}
