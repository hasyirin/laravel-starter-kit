<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Livewire\Components\Tab;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Livewire;

trait InteractsWithTabs
{
    public string $tab = '';

    /**
     * @var list<string>
     */
    #[Locked]
    public array $visitedTabs = [];

    private ?string $tabBeforeUpdate = null;

    /**
     * @var array{visible: list<Tab>, available: list<Tab>, current: ?Tab, panels: list<Tab>}|null
     */
    private ?array $tabState = null;

    /**
     * @return list<Tab>
     */
    abstract public function tabs(): array;

    public function queryTabAs(): string
    {
        return 'tab';
    }

    /**
     * @return array{tab: array{as: string, history: bool, except: string}}
     */
    public function queryStringInteractsWithTabs(): array
    {
        return [
            'tab' => [
                'as' => $this->queryTabAs(),
                'history' => true,
                'except' => '',
            ],
        ];
    }

    public function mountInteractsWithTabs(): void
    {
        $availableTabs = $this->availableTabs();

        abort_if($availableTabs === [], 404);

        $this->selectTab($availableTabs, $this->findTab($availableTabs, $this->tab) ?? $availableTabs[0]);
    }

    public function updatingInteractsWithTabs(string $property): void
    {
        if ($property === 'tab') {
            $this->tabBeforeUpdate = $this->tab;
        }
    }

    public function updatedInteractsWithTabs(string $property): void
    {
        if ($property !== 'tab') {
            return;
        }

        $availableTabs = $this->availableTabs();
        $tab = $this->resolveTab($availableTabs, $this->tab);

        if ($tab === null) {
            $this->tab = $this->tabBeforeUpdate ?? '';
            $this->forgetTabState();

            return;
        }

        $this->selectTab($availableTabs, $tab);
    }

    public function switchTab(string $slug): void
    {
        $availableTabs = $this->availableTabs();
        $tab = $this->findTab($availableTabs, $slug);

        if ($tab !== null) {
            $this->selectTab($availableTabs, $tab);
        }
    }

    #[Computed]
    public function currentTab(): ?Tab
    {
        return $this->tabState()['current'];
    }

    /**
     * @return list<Tab>
     */
    #[Computed]
    public function visibleTabs(): array
    {
        return $this->tabState()['visible'];
    }

    /**
     * @return list<Tab>
     */
    #[Computed]
    public function tabPanels(): array
    {
        return $this->tabState()['panels'];
    }

    public function renderingInteractsWithTabs(View $view): void
    {
        $this->forgetTabState();

        $tabState = $this->tabState();
        $currentTab = $tabState['current'];

        if ($currentTab !== null) {
            $this->tab = $this->storedSlug($tabState['available'], $currentTab);
            $this->rememberKeptAliveTab($currentTab);
        }

        $view->with([
            'tabs' => $tabState['visible'],
            'currentTab' => $currentTab,
            'queryTabAs' => $this->queryTabAs(),
            'tabPanels' => $tabState['panels'],
        ]);
    }

    /**
     * @return list<Tab>
     */
    protected function availableTabs(): array
    {
        return $this->partitionTabs()['available'];
    }

    /**
     * @return array{visible: list<Tab>, available: list<Tab>}
     */
    private function partitionTabs(): array
    {
        $visibleTabs = [];
        $availableTabs = [];

        foreach ($this->tabs() as $tab) {
            if (! $tab->isVisible()) {
                continue;
            }

            $visibleTabs[] = $tab;

            if (! $tab->isDisabled()) {
                $availableTabs[] = $tab;
            }
        }

        return ['visible' => $visibleTabs, 'available' => $availableTabs];
    }

    /**
     * @return array{visible: list<Tab>, available: list<Tab>, current: ?Tab, panels: list<Tab>}
     */
    private function tabState(): array
    {
        if ($this->tabState !== null) {
            return $this->tabState;
        }

        ['visible' => $visibleTabs, 'available' => $availableTabs] = $this->partitionTabs();
        $defaultTab = $availableTabs[0] ?? null;
        $currentTab = $this->resolveTab($availableTabs, $this->tab) ?? $defaultTab;
        $pageUrl = Livewire::originalUrl();
        $pageQuery = $this->pageQuery();

        foreach ($visibleTabs as $tab) {
            $isDefault = $tab === $defaultTab;

            $tab->url($this->tabUrl($pageUrl, $pageQuery, $isDefault ? null : $tab->slug()))->markDefault($isDefault);
        }

        $panels = array_values(array_filter(
            $availableTabs,
            fn (Tab $tab): bool => $tab === $currentTab || ($tab->isKeptAlive() && in_array($tab->slug(), $this->visitedTabs, true)),
        ));

        return $this->tabState = [
            'visible' => $visibleTabs,
            'available' => $availableTabs,
            'current' => $currentTab,
            'panels' => $panels,
        ];
    }

    private function forgetTabState(): void
    {
        $this->tabState = null;

        unset($this->currentTab, $this->visibleTabs, $this->tabPanels);
    }

    /**
     * @param  list<Tab>  $availableTabs
     */
    private function selectTab(array $availableTabs, Tab $tab): void
    {
        $this->tab = $this->storedSlug($availableTabs, $tab);
        $this->rememberKeptAliveTab($tab);
        $this->forgetTabState();
    }

    /**
     * @param  list<Tab>  $availableTabs
     */
    private function storedSlug(array $availableTabs, Tab $tab): string
    {
        return $tab->slug() === ($availableTabs[0] ?? null)?->slug() ? '' : $tab->slug();
    }

    /**
     * @param  list<Tab>  $availableTabs
     */
    private function resolveTab(array $availableTabs, string $slug): ?Tab
    {
        return $slug === '' ? $availableTabs[0] ?? null : $this->findTab($availableTabs, $slug);
    }

    /**
     * @param  list<Tab>  $availableTabs
     */
    private function findTab(array $availableTabs, string $slug): ?Tab
    {
        foreach ($availableTabs as $tab) {
            if ($tab->slug() === $slug) {
                return $tab;
            }
        }

        return null;
    }

    private function rememberKeptAliveTab(Tab $tab): void
    {
        if ($tab->isKeptAlive() && ! in_array($tab->slug(), $this->visitedTabs, true)) {
            $this->visitedTabs[] = $tab->slug();
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private function pageQuery(): array
    {
        if (! Livewire::isLivewireRequest()) {
            return request()->query();
        }

        parse_str((string) parse_url((string) request()->header('Referer'), PHP_URL_QUERY), $query);

        return $query;
    }

    /**
     * @param  array<array-key, mixed>  $pageQuery
     */
    private function tabUrl(string $pageUrl, array $pageQuery, ?string $slug): string
    {
        $key = $this->queryTabAs();

        if ($slug === null) {
            unset($pageQuery[$key]);
        } else {
            $pageQuery[$key] = $slug;
        }

        $queryString = http_build_query($pageQuery);

        return $queryString === '' ? $pageUrl : "{$pageUrl}?{$queryString}";
    }
}
