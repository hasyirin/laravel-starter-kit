@php($disabled ??= false)

<x-card>
    <form wire:submit="save" class="space-y-6">
        <x-input wire:model="form.name" :label="__('common/inputs.name')" autocomplete="off" required :$disabled />

        <x-input wire:model="form.email" type="email" :label="__('common/inputs.email')" autocomplete="off" required :$disabled>
            @isset($user)
                @unless($user->hasVerifiedEmail())
                    <x-slot:actions>
                        <flux:badge size="sm" color="amber">{{ __('user/users.unverified') }}</flux:badge>

                        @can('update', $user)
                            <flux:modal.trigger name="verify-email">
                                <flux:button size="xs" variant="ghost">{{ __('user/users.mark_as_verified') }}</flux:button>
                            </flux:modal.trigger>
                        @endcan
                    </x-slot:actions>
                @endunless
            @endisset
        </x-input>

        <x-input.password wire:model="form.password" :label="__('common/inputs.password')" autocomplete="new-password" :required="! isset($user)" :$disabled />

        <x-input.password
            wire:model="form.password_confirmation"
            :label="__('common/inputs.confirm_password')"
            autocomplete="new-password"
            :required="! isset($user)"
            :$disabled
        />

        <x-select wire:model.live="form.organization_id" :label="__('user/users.organization')" :options="$this->organizations" clearable :$disabled />

        <x-select
            wire:model="form.branch_id"
            :label="__('user/users.branch')"
            :options="$this->branches"
            clearable
            :disabled="$disabled || $this->form->organization_id === null"
            wire:key="branch-{{ $this->form->organization_id }}"
        />

        <x-select
            wire:model.live="form.department_id"
            :label="__('user/users.department')"
            :options="$this->departments"
            clearable
            :disabled="$disabled || $this->form->organization_id === null"
            wire:key="department-{{ $this->form->organization_id }}"
        />

        <x-select
            wire:model="form.position_id"
            :label="__('user/users.position')"
            :options="$this->positions"
            clearable
            :disabled="$disabled || $this->form->department_id === null"
            wire:key="position-{{ $this->form->organization_id }}-{{ $this->form->department_id }}"
        />

        @unless($disabled)
            <div class="flex gap-2">
                <flux:button variant="primary" type="submit">{{ $submit }}</flux:button>
            </div>
        @endunless
    </form>
</x-card>
