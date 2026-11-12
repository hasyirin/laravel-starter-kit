<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('api/api-clients.title'), 'href' => route('system.api-clients.index')], ['label' => $apiClient->name]]" />

        <flux:heading size="xl">{{ $apiClient->name }}</flux:heading>

        @include('livewire.pages.system.api-client.partials.details', ['submit' => __('common/buttons.update')])

        <livewire:pages.system.api-client.tabs :$apiClient />
    </div>
</flux:main>
