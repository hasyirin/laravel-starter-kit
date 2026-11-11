<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[
                ['label' => __('geography/geography.title'), 'href' => route('internal.controls.geography.index')],
                ['label' => __('geography/geography.tabs.countries'), 'href' => route('internal.controls.geography.index', ['tab' => 'countries'])],
                ['label' => __('geography/countries.create.title')],
            ]"
        />

        <flux:heading size="xl">{{ __('geography/countries.create.title') }}</flux:heading>

        @include('livewire.pages.internal.controls.geography.country.partials.details', ['submit' => __('common/buttons.save')])
    </div>
</flux:main>
