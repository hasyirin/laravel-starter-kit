<x-layouts::auth :title="__('auth/two-factor-challenge.title')">
    <div class="flex flex-col gap-6">
        <div
            class="relative h-auto w-full"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',
                focusOtp() {
                    this.$nextTick(() => this.$refs.otp?.querySelector('input')?.focus());
                },
                init() {
                    if (! this.showRecoveryInput) {
                        this.focusOtp();
                    }
                },
                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;

                    this.code = '';
                    this.recovery_code = '';

                    $nextTick(() => {
                        this.showRecoveryInput
                            ? this.$refs.recovery_code?.focus()
                            : this.focusOtp();
                    });
                },
            }"
        >
            <div x-show="!showRecoveryInput">
                <x-auth-header :title="__('auth/two-factor-challenge.code.heading')" :description="__('auth/two-factor-challenge.code.description')" />
            </div>

            <div x-show="showRecoveryInput">
                <x-auth-header :title="__('auth/two-factor-challenge.recovery.heading')" :description="__('auth/two-factor-challenge.recovery.description')" />
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}">
                @csrf

                <div class="space-y-5 text-center">
                    <div x-show="!showRecoveryInput">
                        <div class="my-5 flex items-center justify-center" x-ref="otp">
                            <flux:otp x-model="code" length="6" name="code" label="OTP Code" label:sr-only class="mx-auto" />
                        </div>
                    </div>

                    <div x-show="showRecoveryInput">
                        <div class="my-5">
                            <flux:input
                                type="text"
                                name="recovery_code"
                                x-ref="recovery_code"
                                x-bind:required="showRecoveryInput"
                                autocomplete="one-time-code"
                                x-model="recovery_code"
                            />
                        </div>

                        @error('recovery_code')
                            <flux:text color="red">{{ $message }}</flux:text>
                        @enderror
                    </div>

                    <flux:button variant="primary" type="submit" class="w-full">{{ __('common/buttons.continue') }}</flux:button>
                </div>

                <div class="mt-5 space-x-0.5 text-center text-sm leading-5">
                    <span class="opacity-50">{{ __('auth/two-factor-challenge.or_you_can') }}</span>
                    <div class="inline cursor-pointer font-medium underline opacity-80">
                        <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('auth/two-factor-challenge.use_recovery_code') }}</span>
                        <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('auth/two-factor-challenge.use_authentication_code') }}</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
