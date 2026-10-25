<x-layouts::auth :title="__('auth/reset-password.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth/reset-password.heading')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}" />

            <!-- Email Address -->
            <flux:input name="email" value="{{ request('email') }}" :label="__('common/inputs.email')" type="email" required autocomplete="email" />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('common/inputs.password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('common/inputs.password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('common/inputs.confirm_password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('common/inputs.confirm_password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="reset-password-button">
                    {{ __('auth/reset-password.submit') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
