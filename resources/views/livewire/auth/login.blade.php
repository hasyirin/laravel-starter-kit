<x-layouts::auth :title="__('auth/login.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth/login.heading')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-input
                name="email"
                :label="__('common/inputs.email_address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <x-input.password
                name="password"
                :label="__('common/inputs.password')"
                required
                autocomplete="current-password"
                :placeholder="__('common/inputs.password')"
            >
                @if(Route::has('password.request'))
                    <x-slot:hint>
                        <flux:link :href="route('password.request')" wire:navigate>{{ __('auth/login.forgot_password') }}</flux:link>
                    </x-slot:hint>
                @endif
            </x-input.password>

            <!-- Remember Me -->
            <x-checkbox name="remember" :label="__('auth/login.remember')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">{{ __('auth/login.submit') }}</flux:button>
            </div>
        </form>

        @if(Route::has('register'))
            <div class="space-x-1 text-center text-sm text-zinc-600 rtl:space-x-reverse dark:text-zinc-400">
                <span>{{ __('auth/login.no_account') }}</span>
                <flux:link :href="route('register')" wire:navigate>{{ __('auth/login.sign_up') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>
