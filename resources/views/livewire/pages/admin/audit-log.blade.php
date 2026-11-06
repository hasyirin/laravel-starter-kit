<flux:main container>
    <x-breadcrumbs :items="[['label' => __('navigation.audit_log')]]" class="mb-6" />

    <flux:heading size="xl" class="mb-6">{{ __('navigation.audit_log') }}</flux:heading>

    <livewire:data-tables.admin.audit-log-data-table />

    <livewire:pages.admin.audit-entry-modal />
</flux:main>
