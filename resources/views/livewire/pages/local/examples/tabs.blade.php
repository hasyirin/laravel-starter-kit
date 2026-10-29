<div>
    @if($bar === 'card')
        <x-card.tabs
            :$tabs
            :$currentTab
            :panels="$tabPanels"
            :query-as="$queryTabAs"
            :variant="$variant ?? 'segmented'"
            :$strip
            :$orientation
            :params="$contentParams"
            :aria-label="$ariaLabel"
        />
    @elseif($bar === 'navlist')
        <div class="flex gap-6">
            <x-tabs.navlist :$tabs :$currentTab :query-as="$queryTabAs" :aria-label="$ariaLabel" class="w-40 shrink-0" />
            <x-tabs.content :panels="$tabPanels" :$currentTab orientation="vertical" class="min-w-0 flex-1" />
        </div>
    @else
        @if($bar === 'navbar')
            <x-tabs.navbar :$tabs :$currentTab :query-as="$queryTabAs" :aria-label="$ariaLabel" />
        @else
            <x-tabs :$tabs :$currentTab :query-as="$queryTabAs" :$variant :aria-label="$ariaLabel" />
        @endif

        <x-tabs.content :panels="$tabPanels" :$currentTab class="mt-4" />
    @endif
</div>
