<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[
                ['label' => __('geography/geography.title'), 'href' => route('internal.controls.geography.index')],
                ['label' => __('geography/geography.tabs.countries'), 'href' => route('internal.controls.geography.index', ['tab' => 'countries'])],
                ['label' => $country->name, 'href' => route('internal.controls.geography.countries.show', $country)],
                ['label' => __('geography/countries.tabs.states'), 'href' => route('internal.controls.geography.countries.show', ['country' => $country, 'tab' => 'states'])],
                ['label' => $state->name],
            ]"
        />

        <flux:heading size="xl">{{ $state->name }}</flux:heading>

        @include('livewire.pages.internal.controls.geography.state.partials.details', ['submit' => __('common/buttons.update')])

        <livewire:pages.internal.controls.geography.state.tabs :$state />
    </div>
</flux:main>
