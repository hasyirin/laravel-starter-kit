<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[
                ['label' => __('geography/geography.title'), 'href' => route('internal.controls.geography.index')],
                ['label' => __('geography/geography.tabs.countries'), 'href' => route('internal.controls.geography.index', ['tab' => 'countries'])],
                ['label' => $country->name],
            ]"
        />

        <flux:heading size="xl">{{ $country->name }}</flux:heading>

        @include('livewire.pages.internal.controls.geography.country.partials.details', ['submit' => __('common/buttons.update')])

        <livewire:pages.internal.controls.geography.country.tabs :$country />
    </div>
</flux:main>
