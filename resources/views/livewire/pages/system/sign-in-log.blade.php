<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('navigation.sign_in_log')]]" />

        <flux:heading size="xl">{{ __('navigation.sign_in_log') }}</flux:heading>

        <livewire:data-tables.system.sign-in-log-data-table />
    </div>
</flux:main>
