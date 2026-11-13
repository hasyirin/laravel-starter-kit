<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('navigation.api_log')]]" />

        <flux:heading size="xl">{{ __('navigation.api_log') }}</flux:heading>

        <livewire:data-tables.system.api-log-data-table />
    </div>

    <livewire:pages.system.api-call-modal />
</flux:main>
