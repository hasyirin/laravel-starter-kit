<flux:main container>
    <x-breadcrumbs
        :items="[['label' => __('permission/roles.title'), 'href' => route('admin.controls.roles.index')], ['label' => $role->label]]"
        class="mb-6"
    />

    <flux:heading size="xl" class="mb-6">{{ $role->label }}</flux:heading>

    @include('livewire.pages.admin.controls.role.partials.details', ['submit' => __('common/buttons.update')])

    <div class="mt-10">
        <livewire:pages.admin.controls.role.tabs :$role />
    </div>
</flux:main>
