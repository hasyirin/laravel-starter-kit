<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('navigation.audit_log')]]" />

        <flux:heading size="xl">{{ __('navigation.audit_log') }}</flux:heading>

        <livewire:data-tables.admin.audit-log-data-table />
    </div>

    <livewire:pages.admin.audit-entry-modal />
</flux:main>
