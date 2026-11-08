<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[
                ['label' => __('organization/organizations.title'), 'href' => route('internal.controls.organizations.index')],
                ['label' => $organization->name, 'href' => route('internal.controls.organizations.show', $organization)],
                ['label' => __('organization/organizations.tabs.branches'), 'href' => route('internal.controls.organizations.show', ['organization' => $organization, 'tab' => 'branches'])],
                ['label' => $branch->name],
            ]"
        />

        <div class="flex items-center gap-2">
            <flux:heading size="xl">{{ $branch->name }}</flux:heading>

            @if($branch->is_owner)
                <flux:badge size="sm">{{ __('organization/organizations.owner') }}</flux:badge>
            @endif
        </div>

        @include('livewire.pages.internal.controls.organization.branch.partials.details', ['submit' => __('common/buttons.update')])

        <livewire:pages.internal.controls.organization.branch.tabs :$branch />
    </div>
</flux:main>
