<form wire:submit="save">
    <x-card body="divided" body-class="grid gap-6 sm:grid-cols-2">
        <x-slot:heading level="2">
            {{ __('user/settings.user_information.heading') }}
        </x-slot:heading>

        <x-input wire:model="name" :label="__('common/inputs.name')" type="text" required autocomplete="name" />

        <div>
            <x-input wire:model="email" :label="__('common/inputs.email')" type="email" case="lower" required autocomplete="email" />

            @if($this->unverified)
                <flux:text class="mt-2">
                    {{ __('user/settings.user_information.unverified') }}

                    <flux:link class="cursor-pointer text-sm" wire:click.prevent="resendVerificationNotification">
                        {{ __('user/settings.user_information.resend_verification') }}
                    </flux:link>
                </flux:text>
            @endif
        </div>

        <div>
            <x-input.phone wire:model="phone" :label="__('common/inputs.phone')" :required="$this->phoneRequired" autocomplete="tel" />

            @if($this->phoneUnverified)
                <flux:text class="mt-2">
                    {{ __('user/settings.user_information.phone_unverified') }}

                    <flux:link class="text-sm" :href="route('phone-verification.notice')" wire:navigate>
                        {{ __('user/settings.user_information.verify_phone') }}
                    </flux:link>
                </flux:text>
            @endif
        </div>

        <x-slot:footer>
            <flux:card.actions>
                <flux:button variant="primary" type="submit">{{ __('common/buttons.save') }}</flux:button>
            </flux:card.actions>
        </x-slot:footer>
    </x-card>
</form>
