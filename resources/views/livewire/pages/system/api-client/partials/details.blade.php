@php($disabled ??= false)

<x-card.group form wire:submit="save" :$disabled>
    <x-card accordion body="divided" body-class="grid gap-6 sm:grid-cols-2">
        <x-slot:heading level="2">
            {{ __('api/api-clients.details') }}
        </x-slot:heading>

        <x-input wire:model="form.name" :label="__('common/inputs.name')" autocomplete="off" required />

        <x-input.number
            wire:model="form.rate_limit_per_minute"
            :label="__('api/api-clients.rate_limit')"
            :suffix="__('api/api-clients.per_minute')"
            decimals="0"
            comma=""
            unsigned
            required
        />

        <x-textarea wire:model="form.description" :label="__('api/api-clients.description')" rows="3" class="sm:col-span-2" />

        @if($form->services() !== [])
            <x-select
                wire:model="form.service"
                :label="__('api/api-clients.service')"
                :description="__('api/api-clients.service_description')"
                :options="$form->services()"
                :placeholder="__('api/api-clients.no_service')"
                clearable
                class="sm:col-span-2"
            />
        @endif

        <div class="sm:col-span-2">
            <x-switch wire:model="form.enabled" :label="__('api/api-clients.enabled')" :description="__('api/api-clients.enabled_description')" />
        </div>

        <x-textarea
            wire:model.live.blur="form.allowed_ip_addresses"
            :label="__('api/api-clients.allowed_ip_addresses')"
            :description="__('api/api-clients.allowed_ip_addresses_description')"
            rows="4"
            input-class="font-mono"
            class="sm:col-span-2"
        />

        <div class="sm:col-span-2">
            <x-switch wire:model.live="form.allows_any_ip_address" :label="__('api/api-clients.allows_any_ip_address')" />
        </div>

        @if($form->allows_any_ip_address)
            <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('api/api-clients.any_ip_address_warning')" class="sm:col-span-2" />
        @elseif($form->allowedIpAddresses()->isEmpty())
            <flux:callout icon="information-circle" :heading="__('api/api-clients.no_ip_address_note')" class="sm:col-span-2" />
        @endif
    </x-card>

    <x-card body="divided">
        <x-slot:footer>
            <flux:card.actions>
                <flux:button :href="route('system.api-clients.index')" variant="subtle" wire:navigate>{{ __('common/buttons.back') }}</flux:button>

                @unless($disabled)
                    <flux:button variant="primary" type="submit">{{ $submit }}</flux:button>
                @endunless
            </flux:card.actions>
        </x-slot:footer>
    </x-card>
</x-card.group>
