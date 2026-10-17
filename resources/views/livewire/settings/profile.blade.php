<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('user/settings.profile.title') }}</flux:heading>

    <x-settings.layout :heading="__('user/settings.profile.heading')" :subheading="__('user/settings.profile.description')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('common/inputs.name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('common/inputs.email')" type="email" required autocomplete="email" />

                @if($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('user/settings.profile.unverified') }}

                            <flux:link class="cursor-pointer text-sm" wire:click.prevent="resendVerificationNotification">
                                {{ __('user/settings.profile.resend_verification') }}
                            </flux:link>
                        </flux:text>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('common/buttons.save') }}</flux:button>
            </div>
        </form>

        @if($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
