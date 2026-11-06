<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('permission/roles.title'), 'href' => route('internal.controls.roles.index')], ['label' => $role->label]]" />

        <flux:heading size="xl">{{ $role->label }}</flux:heading>

        @include('livewire.pages.internal.controls.role.partials.details', ['submit' => __('common/buttons.update')])

        <livewire:pages.internal.controls.role.tabs :$role />
    </div>
</flux:main>
