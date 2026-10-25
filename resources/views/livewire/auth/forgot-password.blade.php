<x-layouts::auth :title="__('auth/forgot-password.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth/forgot-password.heading')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input name="email" :label="__('common/inputs.email_address')" type="email" required autofocus placeholder="email@example.com" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('auth/forgot-password.submit') }}
            </flux:button>
        </form>

        <div class="space-x-1 text-center text-sm text-zinc-400 rtl:space-x-reverse">
            <span>{{ __('auth/forgot-password.return_to') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('auth/forgot-password.log_in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
