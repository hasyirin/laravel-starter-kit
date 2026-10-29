@props([
    'tabs',
    'currentTab' => null,
    'queryAs' => 'tab',
    'ariaLabel' => null,
])

@php
    $defaultSlug = collect($tabs)->first(fn (App\Livewire\Components\Tab $tab): bool => $tab->isDefault())?->slug() ?? '';
@endphp

@if($tabs !== [])
    <flux:tabs
        :attributes="$attributes->merge(['aria-label' => $ariaLabel, 'x-data' => 'tabs', 'data-tabs-key' => $queryAs, 'data-tabs-default' => $defaultSlug])"
    >
        <div class="contents">
            @foreach($tabs as $tab)
                @php
                    $isDisabled = $tab->isDisabled();
                    $tooltip = $tab->resolvedTooltip($isDisabled);
                    $isSelected = $tab->slug() === $currentTab?->slug();
                    $badge = $tab->resolvedBadge();
                    $key = 'tab-'.$tab->slug();
                @endphp

                @if($tooltip)
                    <flux:tooltip :content="$tooltip" :wire:key="$key">
                @endif
                <flux:tab
                    :href="$isDisabled ? null : $tab->resolvedUrl()"
                    :as="$isDisabled ? 'div' : null"
                    :selected="$isSelected"
                    :aria-selected="$isSelected ? 'true' : 'false'"
                    :icon="$tab->resolvedIcon()"
                    :aria-disabled="$isDisabled ? 'true' : null"
                    :class="$isDisabled ? 'cursor-default opacity-50' : null"
                    :wire:key="$tooltip ? null : $key"
                    data-tab="{{ $tab->slug() }}"
                >
                    {{ $tab->resolvedLabel() }}

                    @if($badge !== null && $badge !== '')
                        <flux:badge size="sm" :color="$tab->resolvedBadgeColor()">{{ $badge }}</flux:badge>
                    @endif
                </flux:tab>
                @if($tooltip)
                    </flux:tooltip>
                @endif
            @endforeach
        </div>
    </flux:tabs>
@endif
