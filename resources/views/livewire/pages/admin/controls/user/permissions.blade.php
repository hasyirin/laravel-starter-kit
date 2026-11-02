<div
    x-data="permissionTree"
    data-permission-tree="{{ Js::encode([
            'tree' => $this->tree,
            'direct' => $this->direct,
            'fromRoles' => (object) $this->fromRoles,
            'locked' => $this->locked,
            'readOnly' => $this->readOnly,
            'text' => [
                'count' => __('user/users.permissions.count'),
                'one' => trans_choice('user/users.permissions.unsaved', 1),
                'other' => trans_choice('user/users.permissions.unsaved', 2, ['count' => ':count']),
            ],
        ]) }}"
>
    <x-card>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-4">
            <x-input
                x-model="search"
                icon="magnifying-glass"
                :placeholder="__('user/users.permissions.search')"
                :aria-label="__('user/users.permissions.search')"
                class="min-w-60 flex-1"
            />

            <x-switch x-on:change="onlyGranted = $event.target.checked" :label="__('user/users.permissions.only_granted')" />

            <x-switch x-on:change="expandAll = $event.target.checked" :label="__('user/users.permissions.expand_all')" />
        </div>

        <div class="[&_mark]:rounded-xs [&_mark]:bg-yellow-200 [&_mark]:text-inherit dark:[&_mark]:bg-yellow-500/40 mt-6 text-sm">
            @include('livewire.pages.admin.controls.user.partials.permission-chips', ['permissions' => $this->tree['permissions'], 'fromRoles' => $this->fromRoles, 'class' => null])

            @foreach($this->tree['children'] as $node)
                @include('livewire.pages.admin.controls.user.partials.permission-node', ['node' => $node, 'fromRoles' => $this->fromRoles])
            @endforeach
        </div>

        <x-slot:footer x-show="changes > 0 && !readOnly" x-cloak class="flex items-center justify-end gap-2">
            <flux:text class="me-auto" x-text="changesText" data-unsaved></flux:text>

            <flux:button variant="filled" x-on:click="cancel">{{ __('common/buttons.cancel') }}</flux:button>

            <flux:button variant="primary" x-on:click="save" data-permission-save>{{ __('common/buttons.save') }}</flux:button>
        </x-slot:footer>
    </x-card>
</div>
