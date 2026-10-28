<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Local\Examples;

use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Tabs extends Component
{
    use InteractsWithTabs;

    #[Locked]
    public string $queryAs = 'tab';

    #[Locked]
    public string $bar = 'tabs';

    #[Locked]
    public ?string $variant = null;

    #[Locked]
    public bool $strip = true;

    #[Locked]
    public bool $nested = false;

    #[Locked]
    public string $orientation = 'horizontal';

    /**
     * @var array<string, mixed>
     */
    #[Locked]
    public array $contentParams = [];

    #[Locked]
    public ?string $ariaLabel = null;

    public function queryTabAs(): string
    {
        return $this->queryAs;
    }

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return array_values(array_filter([
            Tab::make('profile')
                ->label((string) __('docs.examples.tabs.profile'))
                ->icon('user')
                ->content(TabPanel::class)
                ->params(['label' => __('docs.examples.tabs.display_name')])
                ->keepAlive(),
            Tab::make('notes')
                ->label((string) __('docs.examples.tabs.notes'))
                ->icon('pencil-square')
                ->badge('3')
                ->content(TabPanel::class)
                ->params(['label' => __('docs.examples.tabs.note')]),
            $this->nested ? Tab::make('settings')
                ->label((string) __('docs.examples.tabs.settings'))
                ->icon('cog-6-tooth')
                ->content(self::class)
                ->params(['queryAs' => "{$this->queryAs}-settings"]) : null,
            Tab::make('billing')
                ->label((string) __('docs.examples.tabs.billing'))
                ->icon('credit-card')
                ->disabled(true)
                ->tooltip(disabled: (string) __('docs.examples.tabs.billing_disabled')),
        ]));
    }

    public function render(): View
    {
        return view('livewire.pages.local.examples.tabs');
    }
}
