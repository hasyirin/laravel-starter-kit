<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('organization/organizations.title')]]" />

        <flux:heading size="xl">{{ __('organization/organizations.title') }}</flux:heading>

        <livewire:data-tables.internal.organization-data-table />
    </div>
</flux:main>
