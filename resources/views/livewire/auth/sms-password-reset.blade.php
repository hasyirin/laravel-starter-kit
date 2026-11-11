<div class="flex flex-col gap-6">
    <x-auth-header :title="__('auth/sms-password-reset.heading')" :description="$showingCodeStep ? null : __('auth/sms-password-reset.description')" />

    @if($showingCodeStep)
        <flux:text class="text-center">{{ __('auth/sms-password-reset.sent') }}</flux:text>

        @if($notice !== '')
            <flux:text class="text-center font-medium">{{ $notice }}</flux:text>
        @endif

        <form wire:submit="confirm" class="flex flex-col gap-6">
            <x-field name="code">
                <flux:otp name="code" wire:model="code" length="6" :aria-label="__('common/inputs.otp_code')" class="mx-auto" data-input-control />
            </x-field>

            <flux:button type="submit" variant="primary" class="w-full">{{ __('common/buttons.continue') }}</flux:button>
        </form>

        <div class="flex flex-col items-center gap-2">
            <flux:error name="phone" />

            <flux:button variant="ghost" class="cursor-pointer text-sm" wire:click="send">{{ __('auth/sms-password-reset.resend') }}</flux:button>

            <flux:button variant="ghost" class="cursor-pointer text-sm" wire:click="changeNumber">
                {{ __('auth/sms-password-reset.change_number') }}
            </flux:button>
        </div>
    @else
        <form wire:submit="send" class="flex flex-col gap-6">
            <x-input.phone wire:model="phone" :label="__('common/inputs.phone')" required autofocus autocomplete="tel" />

            <flux:button variant="primary" type="submit" class="w-full">{{ __('auth/sms-password-reset.send') }}</flux:button>
        </form>
    @endif

    <div class="space-x-1 text-center text-sm text-zinc-400 rtl:space-x-reverse">
        <span>{{ __('auth/forgot-password.return_to') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('auth/forgot-password.log_in') }}</flux:link>
    </div>
</div>
