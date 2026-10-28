@foreach($tabs as $tab)
    @php
        $isDisabled = $tab->isDisabled();
        $tooltip = $tab->resolvedTooltip($isDisabled);
        $key = 'tab-'.$tab->slug();
        $isCurrent = $tab->slug() === $currentTab?->slug();
    @endphp

    @if($tooltip)
        <flux:tooltip :content="$tooltip" :wire:key="$key">
    @endif
    <x-dynamic-component
        :component="$item"
        :href="$isDisabled ? null : $tab->resolvedUrl()"
        :as="$isDisabled ? 'div' : null"
        :current="$isCurrent"
        :aria-current="$isCurrent ? 'page' : null"
        :icon="$tab->resolvedIcon()"
        :badge="$tab->resolvedBadge()"
        :badge-color="$tab->resolvedBadgeColor()"
        :aria-disabled="$isDisabled ? 'true' : null"
        :class="$isDisabled ? 'cursor-default opacity-50' : null"
        :wire:key="$tooltip ? null : $key"
        data-tab="{{ $tab->slug() }}"
    >
        {{ $tab->resolvedLabel() }}
    </x-dynamic-component>
    @if($tooltip)
        </flux:tooltip>
    @endif
@endforeach
