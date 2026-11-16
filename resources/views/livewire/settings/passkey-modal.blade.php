<div>
    <flux:modal wire:model="showing" class="md:min-w-md" @close="close">
        @if($showing)
            <div x-data="passkeyModal" x-on:passkeys:ready.window="checkSupport()" class="space-y-6">
                <flux:heading size="lg">{{ __('common/records.add', ['record' => __('user/passkeys.add.button')]) }}</flux:heading>

                <flux:text x-show="!supported">{{ __('user/passkeys.unsupported') }}</flux:text>

                <form x-show="supported" x-on:submit.prevent="register()" class="space-y-6">
                    <x-input x-model="name" :label="__('user/passkeys.add.name')" :placeholder="__('user/passkeys.add.name_placeholder')" required autofocus />

                    <p x-show="error" x-text="error" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('common/buttons.cancel') }}</flux:button>
                        </flux:modal.close>

                        <flux:button type="submit" variant="primary" x-bind:disabled="loading || !name.trim()">
                            <span x-show="!loading">{{ __('user/passkeys.add.submit') }}</span>
                            <span x-show="loading" x-cloak>{{ __('user/passkeys.add.loading') }}</span>
                        </flux:button>
                    </div>
                </form>
            </div>
        @endif
    </flux:modal>
</div>

@assets
    @vite('resources/js/passkeys.js')
@endassets
