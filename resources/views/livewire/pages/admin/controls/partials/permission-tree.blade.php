<div
    x-data="permissionTree"
    data-permission-tree="{{ Js::encode([
            'tree' => $this->permissionTree,
            'roles' => (object) $this->rolePermissions,
            'locked' => $this->lockedPermissions,
            'readOnly' => ! $this->editable('permissions'),
            'text' => [
                'count' => __('user/users.permissions.count'),
                'from' => __('user/users.permissions.from', ['role' => ':role']),
            ],
        ]) }}"
>
    <div class="flex flex-wrap items-center gap-x-6 gap-y-4">
        <x-input
            x-model="search"
            x-on:keydown.enter.prevent
            icon="magnifying-glass"
            :placeholder="__('user/users.permissions.search')"
            :aria-label="__('user/users.permissions.search')"
            class="min-w-60 flex-1"
        />

        <x-switch x-on:change="onlyGranted = $event.target.checked" :label="__('user/users.permissions.only_granted')" />

        <x-switch x-on:change="expandAll = $event.target.checked" :label="__('user/users.permissions.expand_all')" />
    </div>

    <div class="[&_mark]:rounded-xs [&_mark]:bg-yellow-200 [&_mark]:text-inherit dark:[&_mark]:bg-yellow-500/40 mt-6 text-sm">
        @include('livewire.pages.admin.controls.partials.permission-chips', ['permissions' => $this->permissionTree['permissions'], 'class' => null])

        @foreach($this->permissionTree['children'] as $node)
            @include('livewire.pages.admin.controls.partials.permission-node', ['node' => $node])
        @endforeach
    </div>
</div>
