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
        $defaultTab = $this->availableTabs()[0] ?? null;

        abort_if($defaultTab === null, 404);

        $currentTab = $this->findAvailableTab($this->tab) ?? $defaultTab;

        $this->tab = $this->storedSlug($currentTab);
        $this->rememberKeptAliveTab($currentTab);
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

        $tab = $this->resolveTab($this->tab);

        if ($tab === null) {
            $this->tab = $this->tabBeforeUpdate ?? '';

            return;
        }

        $this->tab = $this->storedSlug($tab);
        $this->rememberKeptAliveTab($tab);
    }

    public function switchTab(string $slug): void
    {
        $tab = $this->findAvailableTab($slug);

        if ($tab === null) {
            return;
        }

        $this->tab = $this->storedSlug($tab);
        $this->rememberKeptAliveTab($tab);
    }

    #[Computed]
    public function currentTab(): ?Tab
    {
        return $this->resolveTab($this->tab) ?? $this->availableTabs()[0] ?? null;
    }

    /**
     * @return list<Tab>
     */
    #[Computed]
    public function visibleTabs(): array
    {
        $defaultSlug = $this->defaultTabSlug();
        $pageUrl = Livewire::originalUrl();
        $pageQuery = $this->pageQuery();

        return array_values(array_map(
            fn (Tab $tab): Tab => $tab
                ->url($this->tabUrl($pageUrl, $pageQuery, $tab->slug() === $defaultSlug ? null : $tab->slug()))
                ->markDefault($tab->slug() === $defaultSlug),
            array_filter($this->tabs(), fn (Tab $tab): bool => $tab->isVisible()),
        ));
    }

    /**
     * @return list<Tab>
     */
    #[Computed]
    public function tabPanels(): array
    {
        $currentSlug = $this->currentTab?->slug();

        return array_values(array_filter(
            $this->visibleTabs,
            fn (Tab $tab): bool => ! $tab->isDisabled() && ($tab->slug() === $currentSlug
                || ($tab->isKeptAlive() && in_array($tab->slug(), $this->visitedTabs, true))),
        ));
    }

    public function renderingInteractsWithTabs(View $view): void
    {
        $currentTab = $this->currentTab;

        if ($currentTab !== null) {
            $this->tab = $this->storedSlug($currentTab);
            $this->rememberKeptAliveTab($currentTab);
        }

        $view->with([
            'tabs' => $this->visibleTabs,
            'currentTab' => $currentTab,
            'defaultTab' => $this->defaultTabSlug(),
            'queryTabAs' => $this->queryTabAs(),
            'tabPanels' => $this->tabPanels,
        ]);
    }

    /**
     * @return list<Tab>
     */
    protected function availableTabs(): array
    {
        return array_values(array_filter(
            $this->tabs(),
            fn (Tab $tab): bool => $tab->isVisible() && ! $tab->isDisabled(),
        ));
    }

    private function defaultTabSlug(): string
    {
        return ($this->availableTabs()[0] ?? null)?->slug() ?? '';
    }

    private function storedSlug(Tab $tab): string
    {
        return $tab->slug() === $this->defaultTabSlug() ? '' : $tab->slug();
    }

    private function resolveTab(string $slug): ?Tab
    {
        return $slug === '' ? $this->availableTabs()[0] ?? null : $this->findAvailableTab($slug);
    }

    private function findAvailableTab(string $slug): ?Tab
    {
        foreach ($this->availableTabs() as $tab) {
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
