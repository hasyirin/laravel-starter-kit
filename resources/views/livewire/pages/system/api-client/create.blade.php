<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[['label' => __('api/api-clients.title'), 'href' => route('system.api-clients.index')], ['label' => __('api/api-clients.create.title')]]"
        />

        <flux:heading size="xl">{{ __('api/api-clients.create.title') }}</flux:heading>

        @include('livewire.pages.system.api-client.partials.details', ['submit' => __('common/buttons.save')])
    </div>
</flux:main>
