<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[
                ['label' => __('geography/geography.title'), 'href' => route('internal.controls.geography.index')],
                ['label' => __('geography/geography.tabs.countries'), 'href' => route('internal.controls.geography.index', ['tab' => 'countries'])],
                ['label' => $country->name, 'href' => route('internal.controls.geography.countries.show', $country)],
                ['label' => __('geography/countries.tabs.states'), 'href' => route('internal.controls.geography.countries.show', ['country' => $country, 'tab' => 'states'])],
                ['label' => $state->name, 'href' => route('internal.controls.geography.countries.states.show', [$country, $state])],
                ['label' => __('geography/states.tabs.cities'), 'href' => route('internal.controls.geography.countries.states.show', ['country' => $country, 'state' => $state, 'tab' => 'cities'])],
                ['label' => __('geography/cities.create.title')],
            ]"
        />

        <flux:heading size="xl">{{ __('geography/cities.create.title') }}</flux:heading>

        @include('livewire.pages.internal.controls.geography.post-office.partials.details', ['submit' => __('common/buttons.save')])
    </div>
</flux:main>
