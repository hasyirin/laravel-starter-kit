<flux:main container>
    <x-breadcrumbs
        :items="[['label' => __('permission/roles.title'), 'href' => route('internal.controls.roles.index')], ['label' => __('permission/roles.create.title')]]"
        class="mb-6"
    />

    <flux:heading size="xl" class="mb-6">{{ __('permission/roles.create.title') }}</flux:heading>

    @include('livewire.pages.internal.controls.role.partials.details', ['submit' => __('common/buttons.save')])
</flux:main>
