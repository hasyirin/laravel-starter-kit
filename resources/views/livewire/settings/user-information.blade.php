<flux:main>
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('user/settings.user_information.title') }}</flux:heading>

    <x-settings.layout :heading="__('user/settings.user_information.heading')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('common/inputs.name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('common/inputs.email')" type="email" required autocomplete="email" />

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

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('common/buttons.save') }}</flux:button>
            </div>
        </form>

        @unless($this->unverified)
            <livewire:settings.delete-user-form />
        @endunless
    </x-settings.layout>
</flux:main>
