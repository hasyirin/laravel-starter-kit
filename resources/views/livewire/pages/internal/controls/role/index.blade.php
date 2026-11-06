<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('permission/roles.title')]]" />

        <flux:heading size="xl">{{ __('permission/roles.title') }}</flux:heading>

        <livewire:data-tables.internal.role-data-table />
    </div>
</flux:main>
