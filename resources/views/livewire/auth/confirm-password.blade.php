<x-layouts::auth :title="__('auth/confirm-password.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth/confirm-password.heading')" :description="__('auth/confirm-password.description')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            :label="__('user/passkeys.confirm.button')"
            :loading-label="__('user/passkeys.confirm.loading')"
            :separator="__('user/passkeys.confirm.separator')"
        />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="__('common/inputs.password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('common/inputs.password')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">{{ __('common/buttons.confirm') }}</flux:button>
        </form>
    </div>
</x-layouts::auth>
