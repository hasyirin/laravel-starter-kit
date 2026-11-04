<div class="space-y-4">
    <x-radio
        wire:model.live="direction"
        variant="segmented"
        input-class="w-fit"
        :aria-label="__('user/users.tabs.audit')"
        :options="['by' => __('user/users.audit.by'), 'on' => __('user/users.audit.on')]"
    />

    <livewire:data-tables.admin.user.audit-log-data-table :$user :$direction :wire:key="'audit-log-'.$direction" />

    <livewire:pages.admin.controls.user.audit-entry-modal :$user />
</div>
