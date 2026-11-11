<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('navigation.message_log')]]" />

        <flux:heading size="xl">{{ __('navigation.message_log') }}</flux:heading>

        <livewire:data-tables.system.message-log-data-table />
    </div>

    <livewire:pages.shared.message-modal />
</flux:main>
