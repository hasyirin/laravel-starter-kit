<x-layouts::auth :title="__('auth/login.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth/login.heading')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
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
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('common/inputs.password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('common/inputs.password')"
                    viewable
                />

                @if(Route::has('password.request'))
                    <flux:link class="absolute end-0 top-0 text-sm" :href="route('password.request')" wire:navigate>
                        {{ __('auth/login.forgot_password') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('auth/login.remember')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">{{ __('auth/login.submit') }}</flux:button>
            </div>
        </form>

        <div class="space-x-1 text-center text-sm text-zinc-600 rtl:space-x-reverse dark:text-zinc-400">
            <span>{{ __('auth/login.no_account') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('auth/login.sign_up') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
