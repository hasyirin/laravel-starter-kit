<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs
            :items="[['label' => __('user/users.title'), 'href' => route('internal.controls.users.index')], ['label' => __('user/users.create.title')]]"
        />

        <flux:heading size="xl">{{ __('user/users.create.title') }}</flux:heading>

        @include('livewire.pages.internal.controls.user.partials.details', ['submit' => __('common/buttons.save')])
    </div>
</flux:main>
