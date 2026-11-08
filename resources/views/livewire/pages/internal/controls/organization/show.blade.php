<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[['label' => __('organization/organizations.title'), 'href' => route('internal.controls.organizations.index')], ['label' => $organization->name]]"
        />

        <div class="flex items-center gap-2">
            <flux:heading size="xl">{{ $organization->name }}</flux:heading>

            @if($organization->is_owner)
                <flux:badge size="sm">{{ __('organization/organizations.owner') }}</flux:badge>
            @endif
        </div>

        @include('livewire.pages.internal.controls.organization.partials.details', ['submit' => __('common/buttons.update')])

        <livewire:pages.internal.controls.organization.tabs :$organization />
    </div>
</flux:main>
