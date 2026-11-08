<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[
                ['label' => __('organization/organizations.title'), 'href' => route('internal.controls.organizations.index')],
                ['label' => $organization->name, 'href' => route('internal.controls.organizations.show', $organization)],
                ['label' => __('organization/organizations.tabs.branches'), 'href' => route('internal.controls.organizations.show', ['organization' => $organization, 'tab' => 'branches'])],
                ['label' => __('organization/branches.create.title')],
            ]"
        />

        <flux:heading size="xl">{{ __('organization/branches.create.title') }}</flux:heading>

        @include('livewire.pages.internal.controls.organization.branch.partials.details', ['submit' => __('common/buttons.save')])
    </div>
</flux:main>
