<x-layouts::auth :title="__('auth/verify-email.title')">
    <div class="mt-4 flex flex-col gap-6">
        <flux:text class="text-center">{{ __('auth/verify-email.description') }}</flux:text>

        @if(session('status') == 'verification-link-sent')
            <flux:text class="!dark:text-green-400 text-center font-medium !text-green-600">{{ __('auth/verify-email.link_sent') }}</flux:text>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">{{ __('auth/verify-email.resend') }}</flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" type="submit" class="cursor-pointer text-sm" data-test="logout-button">
                    {{ __('common/buttons.log_out') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>
