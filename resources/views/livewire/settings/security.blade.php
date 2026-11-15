@use(App\Domain\Shared\Common\Enums\Feature)

<flux:main container>
    <div class="space-y-8">
        @include('partials.settings-heading')

        <flux:heading level="2" class="sr-only">{{ __('user/settings.security.title') }}</flux:heading>

        <x-settings.layout :heading="__('user/settings.password.heading')">
            <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
                <x-input.password wire:model="current_password" :label="__('common/inputs.current_password')" required autocomplete="current-password" />
                <x-input.password
                    wire:model="password"
                    :label="__('common/inputs.new_password')"
                    required
                    autocomplete="new-password"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                />
                <x-input.password
                    wire:model="password_confirmation"
                    :label="__('common/inputs.confirm_password')"
                    required
                    autocomplete="new-password"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                />

                <div class="flex justify-end gap-2">
                    <flux:button variant="primary" type="submit" data-test="update-password-button">{{ __('common/buttons.save') }}</flux:button>
                </div>
            </form>

            @if(Feature::TwoFactorAuthentication->enabled())
                <section class="mt-8">
                    <flux:heading>{{ __('user/settings.two_factor.heading') }}</flux:heading>

                    <div class="mx-auto mt-2 flex w-full flex-col space-y-6 text-sm" wire:cloak>
                        @if($twoFactorEnabled)
                            <div class="space-y-4">
                                <flux:text>{{ __('user/settings.two_factor.enabled_description') }}</flux:text>

                                <div class="flex justify-start">
                                    <flux:button variant="danger" wire:click="disable">{{ __('user/settings.two_factor.disable') }}</flux:button>
                                </div>

                                <livewire:settings.two-factor.recovery-codes />
                            </div>
                        @else
                            <div class="space-y-4">
                                <flux:text variant="subtle">{{ __('user/settings.two_factor.disabled_description') }}</flux:text>

                                <flux:button variant="primary" wire:click="enable">{{ __('user/settings.two_factor.enable') }}</flux:button>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            @if(Feature::TwoFactorAuthentication->enabled())
                <flux:modal name="two-factor-setup-modal" class="max-w-md md:min-w-md" @close="closeModal" wire:model="showingSetupModal">
                    <div class="space-y-6">
                        <div class="flex flex-col items-center space-y-4">
                            <div class="w-auto rounded-full border border-stone-100 bg-white p-0.5 shadow-sm dark:border-stone-600 dark:bg-stone-800">
                                <div
                                    class="relative overflow-hidden rounded-full border border-stone-200 bg-stone-100 p-2.5 dark:border-stone-600 dark:bg-stone-200"
                                >
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
                                <div
                                    class="flex flex-col items-center justify-center space-y-3"
                                    x-data
                                    x-init="$nextTick(() => $el.querySelector('input')?.focus())"
                                >
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
                                    <div
                                        class="flex w-full items-center justify-center rounded-xl border bg-stone-100 p-3 dark:border-stone-700 dark:bg-stone-700"
                                    >
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
                <section class="mt-8">
                    <flux:heading>{{ __('user/passkeys.heading') }}</flux:heading>
                    <flux:subheading>{{ __('user/passkeys.description') }}</flux:subheading>

                    <div class="mx-auto mt-6 flex w-full flex-col space-y-6 text-sm" wire:cloak>
                        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            @forelse($passkeys as $passkey)
                                <div class="flex items-center justify-between p-4 {{ ! $loop->last ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }}">
                                    <div class="flex items-center gap-4">
                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                            <flux:icon.key class="size-5 text-zinc-500 dark:text-zinc-400" />
                                        </div>
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2.5">
                                                <p class="font-medium tracking-tight">{{ $passkey['name'] }}</p>
                                                @if($passkey['authenticator'])
                                                    <flux:badge size="sm">{{ $passkey['authenticator'] }}</flux:badge>
                                                @endif
                                            </div>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ __('user/passkeys.added', ['time' => $passkey['created_at_diff']]) }}
                                                @if($passkey['last_used_at_diff'])
                                                    <span class="mx-1 opacity-50">/</span>
                                                    {{ __('user/passkeys.last_used', ['time' => $passkey['last_used_at_diff']]) }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        icon:variant="outline"
                                        wire:click="confirmDelete({{ $passkey['id'] }})"
                                        class="text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/50"
                                    />
                                </div>
                            @empty
                                <div class="p-8 text-center">
                                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.key class="size-7 text-zinc-400 dark:text-zinc-500" />
                                    </div>
                                    <p class="font-medium">{{ __('user/passkeys.empty.heading') }}</p>
                                </div>
                            @endforelse
                        </div>

                        <x-passkey-registration />
                    </div>
                </section>
            @endif
        </x-settings.layout>
    </div>

    <flux:modal name="delete-passkey-modal" class="max-w-md md:min-w-md" @close="closeDeleteModal" wire:model="showingDeleteModal">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('user/passkeys.remove.heading') }}</flux:heading>
                <flux:text>{{ __('user/passkeys.remove.description', ['name' => $deletingPasskeyName]) }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="outline" wire:click="closeDeleteModal">{{ __('common/buttons.cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="deletePasskey">{{ __('user/passkeys.remove.submit') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</flux:main>
