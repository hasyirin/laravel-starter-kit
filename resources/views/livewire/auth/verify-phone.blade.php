<div class="mt-4 flex flex-col gap-6">
    <flux:text class="text-center">
        @if(auth()->user()->phone === null)
            {{ __('auth/verify-phone.no_phone') }}
        @elseif(! $this->covered)
            {{ __('auth/verify-phone.not_covered', ['phone' => auth()->user()->phone]) }}
        @else
            {{ __('auth/verify-phone.description', ['phone' => auth()->user()->phone]) }}
        @endif
    </flux:text>

    @if($notice !== '')
        <flux:text class="text-center font-medium">{{ $notice }}</flux:text>
    @endif

    @if($this->covered)
        <form wire:submit="confirm" class="flex flex-col gap-6">
            <x-field name="code">
                <flux:otp name="code" wire:model="code" length="6" :aria-label="__('common/inputs.otp_code')" class="mx-auto" data-input-control />
            </x-field>

            <flux:button type="submit" variant="primary" class="w-full">{{ __('auth/verify-phone.submit') }}</flux:button>
        </form>
    @endif

    <div class="flex flex-col items-center justify-between space-y-3">
        @if($this->covered)
            <flux:button variant="ghost" class="cursor-pointer text-sm" wire:click="send">{{ __('auth/verify-phone.resend') }}</flux:button>
        @endif

        <flux:link :href="route('user-information.edit')" class="text-sm" wire:navigate>{{ __('auth/verify-phone.change') }}</flux:link>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button variant="ghost" type="submit" class="cursor-pointer text-sm" data-test="logout-button">{{ __('common/buttons.log_out') }}</flux:button>
        </form>
    </div>
</div>
