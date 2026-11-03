<div>
    @if($tabs !== [])
        <div class="mt-6">
            <x-tabs :$tabs :$currentTab :query-as="$queryTabAs" />

            <x-tabs.content :panels="$tabPanels" :$currentTab class="mt-4" />
        </div>
    @endif
</div>
