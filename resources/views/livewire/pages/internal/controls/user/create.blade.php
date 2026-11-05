<flux:main container>
    <x-breadcrumbs
        :items="[['label' => __('user/users.title'), 'href' => route('internal.controls.users.index')], ['label' => __('user/users.create.title')]]"
        class="mb-6"
    />

    <flux:heading size="xl" class="mb-6">{{ __('user/users.create.title') }}</flux:heading>

    @include('livewire.pages.internal.controls.user.partials.details', ['submit' => __('common/buttons.save')])
</flux:main>
