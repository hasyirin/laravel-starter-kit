<div>
    <x-tabs :$tabs :$currentTab :query-as="$queryTabAs" />

    <x-tabs.content :panels="$tabPanels" :$currentTab class="mt-4" />
</div>
