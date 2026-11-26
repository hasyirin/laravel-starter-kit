@props([
    'optionsRoute' => 'passkey.login-options',
    'submitRoute' => 'passkey.login',
    'label' => __('user/passkeys.verify.button'),
    'loadingLabel' => __('user/passkeys.verify.loading'),
    'separator' => __('user/passkeys.verify.separator'),
    'compact' => false,
])

@assets
    @vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        init() {
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async verify() {
            this.loading = true;
            this.error = null;
            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: '{{ route($optionsRoute) }}',
                        submit: '{{ route($submitRoute) }}',
                    },
                });
                Livewire.navigate(response.redirect || '/dashboard');
            } catch (e) {
                if (e.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }"
>
    <template x-if="supported">
        @if($compact)
            <div class="flex flex-col gap-6">
                <flux:separator :text="$separator" />

                <div class="grid justify-items-center gap-2">
                    <flux:tooltip class="inline-flex">
                        <flux:button variant="outline" square x-on:click="verify()" x-bind:disabled="loading" x-bind:aria-busy="loading" :aria-label="$label">
                            <flux:icon icon="finger-print" variant="mini" x-show="!loading" />
                            <flux:icon icon="loading" variant="mini" x-show="loading" x-cloak />
                        </flux:button>
                        <flux:tooltip.content>
                            <span x-text="loading ? @js($loadingLabel) : @js($label)">{{ $label }}</span>
                        </flux:tooltip.content>
                    </flux:tooltip>
                    <p x-show="error" x-text="error" x-cloak class="text-center text-sm text-red-600 dark:text-red-400"></p>
                </div>
            </div>
        @else
            <div>
                <div class="grid gap-2">
                    <flux:button variant="outline" icon="finger-print" class="w-full" x-on:click="verify()" x-bind:disabled="loading">
                        <span x-show="!loading">{{ $label }}</span>
                        <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
                    </flux:button>
                    <p x-show="error" x-text="error" x-cloak class="text-center text-sm text-red-600 dark:text-red-400"></p>
                </div>

                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-zinc-200 dark:border-zinc-700"></div>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-white px-2 text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">{{ $separator }}</span>
                    </div>
                </div>
            </div>
        @endif
    </template>
</div>
