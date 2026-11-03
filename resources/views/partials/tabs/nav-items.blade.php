@foreach($tabs as $tab)
    @php
        $disabled = $tab->isDisabled();
        $tooltip = $tab->resolvedTooltip($disabled);
        $key = 'tab-'.$tab->slug();
        $isCurrent = $tab->slug() === $currentTab?->slug();
    @endphp

    @if($tooltip)
        <flux:tooltip :content="$tooltip" :wire:key="$key">
    @endif
    <x-dynamic-component
        :component="$item"
        :href="$disabled ? null : $tab->resolvedUrl()"
        :as="$disabled ? 'div' : null"
        :current="$isCurrent"
        :aria-current="$isCurrent ? 'page' : null"
        :icon="$tab->resolvedIcon()"
        :badge="$tab->resolvedBadge()"
        :badge-color="$tab->resolvedBadgeColor()"
        :aria-disabled="$disabled ? 'true' : null"
        :class="$disabled ? 'cursor-default opacity-50' : null"
        :wire:key="$tooltip ? null : $key"
        data-tab="{{ $tab->slug() }}"
    >
        {{ $tab->resolvedLabel() }}
    </x-dynamic-component>
    @if($tooltip)
        </flux:tooltip>
    @endif
@endforeach
