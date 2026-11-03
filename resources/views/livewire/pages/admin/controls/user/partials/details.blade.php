@use(App\Domain\User\Support\Preferences)

@php($disabled ??= false)

<x-card.group form wire:submit="save">
    <x-card accordion body="divided" body-class="grid gap-6 sm:grid-cols-2">
        <x-slot:heading level="2">
            {{ __('user/users.details') }}
        </x-slot:heading>

        <x-input wire:model="form.name" :label="__('common/inputs.name')" autocomplete="off" required :$disabled />

        <x-input wire:model="form.email" type="email" :label="__('common/inputs.email')" autocomplete="off" required :$disabled />

        <x-input.password wire:model="form.password" :label="__('common/inputs.password')" autocomplete="new-password" :required="! isset($user)" :$disabled />

        <x-input.password
            wire:model="form.password_confirmation"
            :label="__('common/inputs.confirm_password')"
            autocomplete="new-password"
            :required="! isset($user)"
            :$disabled
        />

        @isset($user)
            <dl class="grid content-start gap-2">
                <dt class="text-sm font-medium text-zinc-800 dark:text-white">{{ __('user/users.created_at') }}</dt>

                <dd>
                    <flux:text variant="strong">{{ Preferences::current()->datetime($user->created_at) }}</flux:text>
                </dd>
            </dl>

            <dl class="grid content-start gap-2">
                <dt class="text-sm font-medium text-zinc-800 dark:text-white">{{ __('user/users.email_verified_at') }}</dt>

                <dd class="flex flex-wrap items-center gap-2">
                    @if($user->hasVerifiedEmail())
                        <flux:text variant="strong">{{ Preferences::current()->datetime($user->email_verified_at) }}</flux:text>
                    @else
                        <flux:badge size="sm" color="amber">{{ __('user/users.unverified') }}</flux:badge>

                        @can('update', $user)
                            <flux:modal.trigger name="verify-email">
                                <flux:button size="xs" variant="ghost">{{ __('user/users.mark_as_verified') }}</flux:button>
                            </flux:modal.trigger>
                        @endcan
                    @endif
                </dd>
            </dl>
        @endisset
    </x-card>

    <x-card accordion body="divided" body-class="grid gap-6 sm:grid-cols-2">
        <x-slot:heading level="2">
            {{ __('user/users.organization') }}
        </x-slot:heading>

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
            :disabled="$disabled || $this->form->organization_id === null"
            wire:key="position-{{ $this->form->organization_id }}-{{ $this->form->department_id }}"
        />
    </x-card>

    <x-card accordion body="divided" :collapsed="isset($user)">
        <x-slot:heading level="2">
            {{ __('user/users.roles') }}
        </x-slot:heading>

        @if($this->roleOptions->isEmpty())
            <flux:text>{{ __('common/records.none') }}</flux:text>
        @else
            <x-checkbox
                wire:model="form.roles"
                :options="$this->roleOptions"
                :aria-label="__('user/users.roles')"
                input-class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4 *:data-flux-field:mb-0!"
            />
        @endif
    </x-card>

    <x-card accordion body="divided" :collapsed="isset($user)">
        <x-slot:heading level="2">
            {{ __('user/users.permissions.heading') }}
        </x-slot:heading>

        @include('livewire.pages.admin.controls.user.partials.permission-tree')
    </x-card>

    <x-card body="divided">
        <x-slot:footer>
            <flux:card.actions>
                <flux:button :href="route('admin.controls.users.index')" variant="subtle" wire:navigate>{{ __('common/buttons.back') }}</flux:button>

                @unless($disabled)
                    <flux:button variant="primary" type="submit">{{ $submit }}</flux:button>
                @endunless
            </flux:card.actions>
        </x-slot:footer>
    </x-card>
</x-card.group>
