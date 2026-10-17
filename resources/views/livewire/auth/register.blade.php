<x-layouts::auth :title="__('auth/register.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth/register.heading')" :description="__('auth/register.description')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('common/inputs.name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('common/inputs.name_placeholder')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('common/inputs.email_address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

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
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">{{ __('auth/register.submit') }}</flux:button>
            </div>
        </form>

        <div class="space-x-1 text-center text-sm text-zinc-600 rtl:space-x-reverse dark:text-zinc-400">
            <span>{{ __('auth/register.has_account') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('auth/register.log_in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
