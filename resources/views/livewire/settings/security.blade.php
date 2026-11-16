@use(App\Domain\Shared\Common\Enums\Feature)
@use(Illuminate\Validation\Rules\Password)

<div>
    <div class="space-y-6">
        <form method="POST" wire:submit="updatePassword">
            <x-card body="divided" body-class="grid gap-6 sm:grid-cols-2">
                <x-slot:heading level="2">
                    {{ __('user/settings.password.heading') }}
                </x-slot:heading>

                <x-input.password wire:model="current_password" :label="__('common/inputs.current_password')" required autocomplete="current-password" />

                <x-input.password
                    wire:model="password"
                    :label="__('common/inputs.new_password')"
                    required
                    autocomplete="new-password"
                    passwordrules="{{ Password::defaults()->toPasswordRulesString() }}"
                />

                <x-input.password
                    wire:model="password_confirmation"
                    :label="__('common/inputs.confirm_password')"
                    required
                    autocomplete="new-password"
                    passwordrules="{{ Password::defaults()->toPasswordRulesString() }}"
                />

                <x-slot:footer>
                    <flux:card.actions>
                        <flux:button variant="primary" type="submit" data-test="update-password-button">{{ __('common/buttons.save') }}</flux:button>
                    </flux:card.actions>
                </x-slot:footer>
            </x-card>
        </form>

        @if(Feature::TwoFactorAuthentication->enabled())
            <x-card body="divided">
                <x-slot:heading level="2">
                    {{ __('user/settings.two_factor.heading') }}
                </x-slot:heading>

                <div class="space-y-6" wire:cloak>
                    @if($twoFactorEnabled)
                        <flux:text>{{ __('user/settings.two_factor.enabled_description') }}</flux:text>

                        <livewire:settings.two-factor.recovery-codes />
                    @else
                        <flux:text variant="subtle">{{ __('user/settings.two_factor.disabled_description') }}</flux:text>
                    @endif
                </div>

                <x-slot:footer>
                    <flux:card.actions>
                        @if($twoFactorEnabled)
                            <flux:button variant="danger" wire:click="disable">{{ __('user/settings.two_factor.disable') }}</flux:button>
                        @else
                            <flux:button variant="primary" wire:click="enable">{{ __('user/settings.two_factor.enable') }}</flux:button>
                        @endif
                    </flux:card.actions>
                </x-slot:footer>
            </x-card>
        @endif

        @if(Feature::Passkeys->enabled())
            <livewire:data-tables.settings.passkey-data-table />
        @endif
    </div>

    @if(Feature::TwoFactorAuthentication->enabled())
        <flux:modal name="two-factor-setup-modal" class="max-w-md md:min-w-md" @close="closeModal" wire:model="showingSetupModal">
            <div class="space-y-6">
                <div class="flex flex-col items-center space-y-4">
                    <div class="w-auto rounded-full border border-stone-100 bg-white p-0.5 shadow-sm dark:border-stone-600 dark:bg-stone-800">
                        <div class="relative overflow-hidden rounded-full border border-stone-200 bg-stone-100 p-2.5 dark:border-stone-600 dark:bg-stone-200">
                            <div
                                class="[&>div]:flex-1 absolute inset-0 flex h-full w-full items-stretch justify-around divide-x divide-stone-200 opacity-50 dark:divide-stone-300"
                            >
                                @for($i = 1; $i <= 5; $i++)
                                    <div></div>
                                @endfor
                            </div>

                            <div
                                class="[&>div]:flex-1 absolute inset-0 flex h-full w-full flex-col items-stretch justify-around divide-y divide-stone-200 opacity-50 dark:divide-stone-300"
                            >
                                @for($i = 1; $i <= 5; $i++)
                                    <div></div>
                                @endfor
                            </div>

                            <flux:icon.qr-code class="relative z-20 dark:text-accent-foreground" />
                        </div>
                    </div>

                    <div class="space-y-2 text-center">
                        <flux:heading size="lg">{{ $this->modalConfig['title'] }}</flux:heading>
                        <flux:text>{{ $this->modalConfig['description'] }}</flux:text>
                    </div>
                </div>

                @if($showingVerificationStep)
                    <div class="space-y-6">
                        <div class="flex flex-col items-center justify-center space-y-3" x-data x-init="$nextTick(() => $el.querySelector('input')?.focus())">
                            <x-field name="code">
                                <flux:otp
                                    name="code"
                                    wire:model="code"
                                    length="6"
                                    :aria-label="__('common/inputs.otp_code')"
                                    class="mx-auto"
                                    data-input-control
                                />
                            </x-field>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:button variant="outline" class="flex-1" wire:click="resetVerification">{{ __('common/buttons.back') }}</flux:button>

                            <flux:button variant="primary" class="flex-1" wire:click="confirmTwoFactor" x-bind:disabled="$wire.code.length < 6">
                                {{ __('common/buttons.confirm') }}
                            </flux:button>
                        </div>
                    </div>
                @else
                    @error('setupData')
                        <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
                    @enderror

                    <div class="flex justify-center">
                        <div class="relative aspect-square w-64 overflow-hidden rounded-lg border border-stone-200 dark:border-stone-700">
                            @empty($qrCodeSvg)
                                <div class="absolute inset-0 flex animate-pulse items-center justify-center bg-white dark:bg-stone-700">
                                    <flux:icon.loading />
                                </div>
                            @else
                                <div x-data class="flex h-full items-center justify-center p-4">
                                    <div
                                        class="rounded bg-white p-3"
                                        :style="$flux.appearance === 'dark' || ($flux.appearance === 'system' && $flux.dark)
                                            ? 'filter: invert(1) brightness(1.5)'
                                            : ''"
                                    >
                                        {!! $qrCodeSvg !!}
                                    </div>
                                </div>
                            @endempty
                        </div>
                    </div>

                    <div>
                        <flux:button :disabled="$errors->has('setupData')" variant="primary" class="w-full" wire:click="showVerificationIfNecessary">
                            {{ $this->modalConfig['buttonText'] }}
                        </flux:button>
                    </div>

                    <div class="space-y-4">
                        <div class="relative flex w-full items-center justify-center">
                            <div class="absolute inset-0 top-1/2 h-px w-full bg-stone-200 dark:bg-stone-600"></div>
                            <span class="relative bg-white px-2 text-sm text-stone-600 dark:bg-stone-800 dark:text-stone-400">
                                {{ __('user/settings.two_factor.manual_entry') }}
                            </span>
                        </div>

                        @empty($manualSetupKey)
                            <div class="flex w-full items-center justify-center rounded-xl border bg-stone-100 p-3 dark:border-stone-700 dark:bg-stone-700">
                                <flux:icon.loading variant="mini" />
                            </div>
                        @else
                            <x-input :value="$manualSetupKey" :aria-label="__('user/settings.two_factor.setup_key')" readonly copyable />
                        @endempty
                    </div>
                @endif
            </div>
        </flux:modal>
    @endif

    @if(Feature::Passkeys->enabled())
        <livewire:settings.passkey-modal />
    @endif
</div>
