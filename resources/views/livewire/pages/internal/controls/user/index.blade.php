<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('user/users.title')]]" />

        <flux:heading size="xl">{{ __('user/users.title') }}</flux:heading>

        <livewire:data-tables.internal.user-data-table />
    </div>

    <livewire:pages.internal.controls.user.impersonate-modal starts-on-index-page />
</flux:main>
