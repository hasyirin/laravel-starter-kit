@props([
    'tabs',
    'currentTab' => null,
    'queryAs' => 'tab',
    'ariaLabel' => null,
])

@php
    $defaultSlug = collect($tabs)->first(fn (App\Livewire\Components\Tab $tab): bool => $tab->default())?->slug() ?? '';
@endphp

@if($tabs !== [])
    <flux:tabs
        :attributes="$attributes->merge(['aria-label' => $ariaLabel, 'x-data' => 'tabs', 'data-tabs-key' => $queryAs, 'data-tabs-default' => $defaultSlug])"
    >
        <div class="contents">
            @foreach($tabs as $tab)
                @php
                    $disabled = $tab->isDisabled();
                    $tooltip = $tab->resolvedTooltip($disabled);
                    $selected = $tab->slug() === $currentTab?->slug();
                    $badge = $tab->resolvedBadge();
                    $key = 'tab-'.$tab->slug();
                @endphp

                @if($tooltip)
                    <flux:tooltip :content="$tooltip" :wire:key="$key">
                @endif
                <flux:tab
                    :href="$disabled ? null : $tab->resolvedUrl()"
                    :as="$disabled ? 'div' : null"
                    :selected="$selected"
                    :aria-selected="$selected ? 'true' : 'false'"
                    :icon="$tab->resolvedIcon()"
                    :aria-disabled="$disabled ? 'true' : null"
                    :class="$disabled ? 'cursor-default opacity-50' : null"
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
