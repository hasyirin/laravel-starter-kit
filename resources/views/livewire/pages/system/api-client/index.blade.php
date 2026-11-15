<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('api/api-clients.title')]]" />

        <flux:heading size="xl">{{ __('api/api-clients.title') }}</flux:heading>

        <livewire:data-tables.system.api-client-data-table />
    </div>
</flux:main>
