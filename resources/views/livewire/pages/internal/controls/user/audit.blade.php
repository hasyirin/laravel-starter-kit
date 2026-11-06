@use(App\Domain\User\Enums\AuditDirection)

<div class="space-y-4">
    <x-radio wire:model.live="direction" variant="segmented" input-class="w-fit" :aria-label="__('user/users.tabs.audit')" :options="AuditDirection::cases()" />

    <livewire:data-tables.internal.user.audit-log-data-table :$user :$direction :wire:key="'audit-log-'.$direction->value" />

    <livewire:pages.internal.controls.user.audit-entry-modal :$user />
</div>
