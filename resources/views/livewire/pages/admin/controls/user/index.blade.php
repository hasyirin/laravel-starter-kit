<flux:main container>
    <x-breadcrumbs :items="[['label' => __('user/users.title')]]" class="mb-6" />

    <flux:heading size="xl" class="mb-6">{{ __('user/users.title') }}</flux:heading>

    <livewire:data-tables.admin.user-data-table />
</flux:main>
