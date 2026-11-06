<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[['label' => __('permission/roles.title'), 'href' => route('internal.controls.roles.index')], ['label' => __('permission/roles.create.title')]]"
        />

        <flux:heading size="xl">{{ __('permission/roles.create.title') }}</flux:heading>

        @include('livewire.pages.internal.controls.role.partials.details', ['submit' => __('common/buttons.save')])
    </div>
</flux:main>
