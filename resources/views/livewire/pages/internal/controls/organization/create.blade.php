<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[['label' => __('organization/organizations.title'), 'href' => route('internal.controls.organizations.index')], ['label' => __('organization/organizations.create.title')]]"
        />

        <flux:heading size="xl">{{ __('organization/organizations.create.title') }}</flux:heading>

        @include('livewire.pages.internal.controls.organization.partials.details', ['submit' => __('common/buttons.save')])
    </div>
</flux:main>
