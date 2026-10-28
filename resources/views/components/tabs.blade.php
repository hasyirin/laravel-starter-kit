@props([
    'tabs',
    'currentTab' => null,
    'queryAs' => 'tab',
    'ariaLabel' => null,
])

@php
    $defaultSlug = collect($tabs)->first(fn (App\Livewire\Components\Tab $tab): bool => ! $tab->isDisabled())?->slug() ?? '';
@endphp

@if($tabs !== [])
    <flux:tabs :attributes="$attributes->merge(['aria-label' => $ariaLabel, 'x-data' => 'tabs('.Js::from($queryAs).', '.Js::from($defaultSlug).')'])">
        @foreach($tabs as $tab)
            @php
                $isDisabled = $tab->isDisabled();
                $tooltip = $tab->resolvedTooltip($isDisabled);
                $badge = $tab->resolvedBadge();
            @endphp

            @if($tooltip)
                <flux:tooltip :content="$tooltip">
            @endif
            <flux:tab
                :href="$isDisabled ? null : $tab->resolvedUrl()"
                :as="$isDisabled ? 'div' : null"
                :selected="$tab->slug() === $currentTab?->slug()"
                :icon="$tab->resolvedIcon()"
                :aria-disabled="$isDisabled ? 'true' : null"
                :class="$isDisabled ? 'pointer-events-none opacity-50' : null"
                data-tab="{{ $tab->slug() }}"
                wire:key="tab-{{ $tab->slug() }}"
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
    </flux:tabs>
@endif
