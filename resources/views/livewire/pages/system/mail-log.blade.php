<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('navigation.mail_log')]]" />

        <flux:heading size="xl">{{ __('navigation.mail_log') }}</flux:heading>

        <livewire:data-tables.system.mail-log-data-table />
    </div>

    <livewire:pages.shared.mail-modal />
</flux:main>
