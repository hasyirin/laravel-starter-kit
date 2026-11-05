<flux:main container>
    <x-breadcrumbs :items="[['label' => __('permission/roles.title')]]" class="mb-6" />

    <flux:heading size="xl" class="mb-6">{{ __('permission/roles.title') }}</flux:heading>

    <livewire:data-tables.internal.role-data-table />
</flux:main>
