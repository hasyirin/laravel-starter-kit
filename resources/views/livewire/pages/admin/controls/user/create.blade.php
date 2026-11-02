<flux:main container>
    <x-breadcrumbs
        :items="[['label' => __('user/users.title'), 'href' => route('admin.controls.users.index')], ['label' => __('user/users.create.title')]]"
        class="mb-6"
    />

    <flux:heading size="xl" class="mb-6">{{ __('user/users.create.title') }}</flux:heading>

    @include('livewire.pages.admin.controls.user.partials.details', ['submit' => __('user/users.create.submit')])
</flux:main>
