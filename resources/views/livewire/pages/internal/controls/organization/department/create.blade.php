<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[
                ['label' => __('organization/organizations.title'), 'href' => route('internal.controls.organizations.index')],
                ['label' => $organization->name, 'href' => route('internal.controls.organizations.show', $organization)],
                ['label' => __('organization/departments.create.title')],
            ]"
        />

        <flux:heading size="xl">{{ __('organization/departments.create.title') }}</flux:heading>

        @include('livewire.pages.internal.controls.organization.department.partials.details', ['submit' => __('common/buttons.save')])
    </div>
</flux:main>
