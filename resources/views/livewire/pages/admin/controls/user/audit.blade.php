<div class="space-y-6">
    <x-radio
        wire:model.live="direction"
        variant="segmented"
        :aria-label="__('user/users.tabs.audit')"
        :options="['by' => __('user/users.audit.by'), 'on' => __('user/users.audit.on')]"
    />

    <livewire:data-tables.admin.user.activity-data-table :user-id="$userId" :$direction :wire:key="'activities-'.$direction" />

    <livewire:pages.admin.controls.user.activity-modal :user-id="$userId" />
</div>
