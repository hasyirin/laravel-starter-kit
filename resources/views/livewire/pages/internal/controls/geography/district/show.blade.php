<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[
                ['label' => __('geography/geography.title'), 'href' => route('internal.controls.geography.index')],
                ['label' => __('geography/geography.tabs.countries'), 'href' => route('internal.controls.geography.index', ['tab' => 'countries'])],
                ['label' => $country->name, 'href' => route('internal.controls.geography.countries.show', $country)],
                ['label' => __('geography/countries.tabs.states'), 'href' => route('internal.controls.geography.countries.show', ['country' => $country, 'tab' => 'states'])],
                ['label' => $state->name, 'href' => route('internal.controls.geography.countries.states.show', [$country, $state])],
                ['label' => __('geography/states.tabs.districts'), 'href' => route('internal.controls.geography.countries.states.show', ['country' => $country, 'state' => $state, 'tab' => 'districts'])],
                ['label' => $district->name],
            ]"
        />

        <flux:heading size="xl">{{ $district->name }}</flux:heading>

        @include('livewire.pages.internal.controls.geography.district.partials.details', ['submit' => __('common/buttons.update')])

        <livewire:pages.internal.controls.geography.district.tabs :$district />
    </div>
</flux:main>
