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
    <flux:navlist :attributes="$attributes->merge(['aria-label' => $ariaLabel, 'x-data' => 'tabs('.Js::from($queryAs).', '.Js::from($defaultSlug).')'])">
        @foreach($tabs as $tab)
            @php
                $isDisabled = $tab->isDisabled();
                $tooltip = $tab->resolvedTooltip($isDisabled);
            @endphp

            @if($tooltip)
                <flux:tooltip :content="$tooltip">
            @endif
            <flux:navlist.item
                :href="$isDisabled ? null : $tab->resolvedUrl()"
                :as="$isDisabled ? 'div' : null"
                :current="$tab->slug() === $currentTab?->slug()"
                :icon="$tab->resolvedIcon()"
                :badge="$tab->resolvedBadge()"
                :badge-color="$tab->resolvedBadgeColor()"
                :aria-disabled="$isDisabled ? 'true' : null"
                :class="$isDisabled ? 'pointer-events-none opacity-50' : null"
                data-tab="{{ $tab->slug() }}"
                wire:key="tab-{{ $tab->slug() }}"
            >
                {{ $tab->resolvedLabel() }}
            </flux:navlist.item>
            @if($tooltip)
                </flux:tooltip>
            @endif
        @endforeach
    </flux:navlist>
@endif
