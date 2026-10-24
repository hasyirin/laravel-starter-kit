<flux:main>
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('user/settings.security.title') }}</flux:heading>

    <x-settings.layout :heading="__('user/settings.password.heading')" :subheading="__('user/settings.password.description')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input
                wire:model="current_password"
                :label="__('common/inputs.current_password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            <flux:input
                wire:model="password"
                :label="__('common/inputs.new_password')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />
            <flux:input
                wire:model="password_confirmation"
                :label="__('common/inputs.confirm_password')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-password-button">{{ __('common/buttons.save') }}</flux:button>
            </div>
        </form>

        @if($canManageTwoFactor)
            <section class="mt-12">
                <flux:heading>{{ __('user/settings.two_factor.heading') }}</flux:heading>
                <flux:subheading>{{ __('user/settings.two_factor.description') }}</flux:subheading>

                <div class="mx-auto flex w-full flex-col space-y-6 text-sm" wire:cloak>
                    @if($twoFactorEnabled)
                        <div class="space-y-4">
                            <flux:text>{{ __('user/settings.two_factor.enabled_description') }}</flux:text>

                            <div class="flex justify-start">
                                <flux:button variant="danger" wire:click="disable">{{ __('user/settings.two_factor.disable') }}</flux:button>
                            </div>

                            <livewire:settings.two-factor.recovery-codes :$requiresConfirmation />
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

        @if($canManageTwoFactor)
            <flux:modal name="two-factor-setup-modal" class="max-w-md md:min-w-md" @close="closeModal" wire:model="showModal">
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

                    @if($showVerificationStep)
                        <div class="space-y-6">
                            <div
                                class="flex flex-col items-center justify-center space-y-3"
                                x-data
                                x-init="$nextTick(() => $el.querySelector('input')?.focus())"
                            >
                                <flux:otp name="code" wire:model="code" length="6" label="OTP Code" label:sr-only class="mx-auto" />
                            </div>

                            <div class="flex items-center space-x-3">
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

                            <div
                                class="flex items-center space-x-2"
                                x-data="{
                                    copied: false,
                                    async copy() {
                                        try {
                                            await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 1500);
                                        } catch (e) {
                                            console.warn('Could not copy to clipboard');
                                        }
                                    }
                                }"
                            >
                                <div class="flex w-full items-stretch rounded-xl border dark:border-stone-700">
                                    @empty($manualSetupKey)
                                        <div class="flex w-full items-center justify-center bg-stone-100 p-3 dark:bg-stone-700">
                                            <flux:icon.loading variant="mini" />
                                        </div>
                                    @else
                                        <input
                                            type="text"
                                            readonly
                                            value="{{ $manualSetupKey }}"
                                            class="w-full bg-transparent p-3 text-stone-900 outline-none dark:text-stone-100"
                                        />

                                        <button @click="copy()" class="cursor-pointer border-l border-stone-200 px-3 transition-colors dark:border-stone-600">
                                            <flux:icon.document-duplicate x-show="!copied" variant="outline">
                                                </flux:icon>
                                                <flux:icon.check x-show="copied" variant="solid" class="text-green-500"></flux:icon>
                                        </button>
                                    @endempty
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:modal>
        @endif

        @if($canManagePasskeys)
            <section class="mt-12">
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
                                <flux:text class="mt-1">{{ __('user/passkeys.empty.description') }}</flux:text>
                            </div>
                        @endforelse
                    </div>

                    <x-passkey-registration />
                </div>
            </section>
        @endif
    </x-settings.layout>

    <flux:modal name="delete-passkey-modal" class="max-w-md md:min-w-md" @close="closeDeleteModal" wire:model="showDeleteModal">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('user/passkeys.remove.heading') }}</flux:heading>
                <flux:text>{{ __('user/passkeys.remove.description', ['name' => $deletingPasskeyName]) }}</flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="outline" wire:click="closeDeleteModal">{{ __('common/buttons.cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="deletePasskey">{{ __('user/passkeys.remove.submit') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</flux:main>
