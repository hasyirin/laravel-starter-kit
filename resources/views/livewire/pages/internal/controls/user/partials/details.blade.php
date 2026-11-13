@use(App\Domain\Shared\Common\Enums\Feature)
@use(App\Domain\User\Support\Preferences)

@php($disabled ??= false)

<x-card.group form wire:submit="save" :$disabled>
    <x-card accordion body="divided" body-class="grid gap-6 sm:grid-cols-2">
        <x-slot:heading level="2">
            {{ __('user/users.user_information') }}
        </x-slot:heading>

        <x-input wire:model="form.name" :label="__('common/inputs.name')" autocomplete="off" required class="sm:col-span-2" />

        <x-input wire:model="form.email" type="email" case="lower" :label="__('common/inputs.email')" autocomplete="off" required />

        <x-input.phone wire:model="form.phone" :label="__('common/inputs.phone')" autocomplete="off" />

        <x-input.password wire:model="form.password" :label="__('common/inputs.password')" autocomplete="new-password" :required="! isset($user)" />

        <x-input.password
            wire:model="form.password_confirmation"
            :label="__('common/inputs.confirm_password')"
            autocomplete="new-password"
            :required="! isset($user)"
        />

        @isset($user)
            <x-slot:footer>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <x-entry :label="__('user/users.created_at')">{{ Preferences::current()->datetime($user->created_at) }}</x-entry>

                    <x-entry :label="__('user/users.email_verified_at')">
                        @if($user->hasVerifiedEmail())
                            {{ Preferences::current()->datetime($user->email_verified_at) }}
                        @else
                            <span class="inline-flex items-center gap-1.5 text-amber-600 dark:text-amber-400">
                                <span class="size-1.5 rounded-full bg-current"></span>
                                {{ __('user/users.unverified') }}
                            </span>

                            @can('update', $user)
                                <flux:modal.trigger name="verify-email">
                                    <flux:link as="button" variant="subtle">{{ __('user/users.mark_as_verified') }}</flux:link>
                                </flux:modal.trigger>
                            @endcan
                        @endif
                    </x-entry>

                    @if(Feature::PhoneVerification->enabled())
                        <x-entry :label="__('user/users.phone_verified_at')">
                            @if($user->phone === null)
                                {{ __('user/users.no_phone') }}
                            @elseif($user->hasVerifiedPhone())
                                {{ Preferences::current()->datetime($user->phone_verified_at) }}
                            @else
                                <span class="inline-flex items-center gap-1.5 text-amber-600 dark:text-amber-400">
                                    <span class="size-1.5 rounded-full bg-current"></span>
                                    {{ __('user/users.unverified') }}
                                </span>

                                @can('update', $user)
                                    <flux:modal.trigger name="verify-phone">
                                        <flux:link as="button" variant="subtle">{{ __('user/users.mark_as_verified') }}</flux:link>
                                    </flux:modal.trigger>
                                @endcan
                            @endif
                        </x-entry>
                    @endif
                </div>
            </x-slot:footer>
        @endisset
    </x-card>

    <x-card accordion body="divided" body-class="grid gap-6 sm:grid-cols-2">
        <x-slot:heading level="2">
            {{ __('user/users.placement') }}
        </x-slot:heading>

        <x-select wire:model.live="form.organization_id" :label="__('user/users.organization')" :options="$this->organizations" clearable />

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

        @include('livewire.pages.internal.controls.partials.permission-tree')
    </x-card>

    <x-card body="divided">
        <x-slot:footer>
            <flux:card.actions>
                <flux:button :href="$back" variant="subtle" wire:navigate>{{ __('common/buttons.back') }}</flux:button>

                @unless($disabled)
                    <flux:button variant="primary" type="submit">{{ $submit }}</flux:button>
                @endunless
            </flux:card.actions>
        </x-slot:footer>
    </x-card>
</x-card.group>
