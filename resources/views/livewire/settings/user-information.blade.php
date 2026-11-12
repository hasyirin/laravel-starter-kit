<flux:main container>
    <div class="space-y-8">
        @include('partials.settings-heading')

        <flux:heading level="2" class="sr-only">{{ __('user/settings.user_information.title') }}</flux:heading>

        <x-settings.layout :heading="__('user/settings.user_information.heading')">
            <form wire:submit="save" class="my-6 w-full space-y-6">
                <x-input wire:model="name" :label="__('common/inputs.name')" type="text" required autofocus autocomplete="name" />

                <div>
                    <x-input wire:model="email" :label="__('common/inputs.email')" type="email" case="lower" required autocomplete="email" />

                    @if($this->unverified)
                        <div>
                            <flux:text class="mt-4">
                                {{ __('user/settings.user_information.unverified') }}

                                <flux:link class="cursor-pointer text-sm" wire:click.prevent="resendVerificationNotification">
                                    {{ __('user/settings.user_information.resend_verification') }}
                                </flux:link>
                            </flux:text>
                        </div>
                    @endif
                </div>

                <div>
                    <x-input.phone wire:model="phone" :label="__('common/inputs.phone')" :required="$this->phoneRequired" autocomplete="tel" />

                    @if($this->phoneUnverified)
                        <flux:text class="mt-4">
                            {{ __('user/settings.user_information.phone_unverified') }}

                            <flux:link class="text-sm" :href="route('phone-verification.notice')" wire:navigate>
                                {{ __('user/settings.user_information.verify_phone') }}
                            </flux:link>
                        </flux:text>
                    @endif
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="primary" type="submit">{{ __('common/buttons.save') }}</flux:button>
                </div>
            </form>

            @unless($this->unverified)
                <livewire:settings.delete-user-form />
            @endunless
        </x-settings.layout>
    </div>
</flux:main>
