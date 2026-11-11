<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('geography/geography.title')]]" />

        <flux:heading size="xl">{{ __('geography/geography.title') }}</flux:heading>

        <livewire:pages.internal.controls.geography.tabs />
    </div>
</flux:main>
