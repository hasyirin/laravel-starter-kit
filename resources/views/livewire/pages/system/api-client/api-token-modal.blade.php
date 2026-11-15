<div>
    <flux:modal wire:model="showing" class="md:min-w-xl">
        @if($showing && $plainToken !== null)
            <div class="space-y-6">
                <flux:heading size="lg">{{ __('api/api-tokens.issued_heading') }}</flux:heading>

                <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('api/api-tokens.issued_warning')" />

                <div x-data>
                    <x-textarea x-ref="token" :label="__('api/api-tokens.api_token')" rows="2" resize="none" input-class="font-mono break-all" readonly>
                        <x-slot:actions>
                            <flux:button
                                size="xs"
                                variant="ghost"
                                icon="clipboard"
                                data-copy-token
                                :aria-label="__('api/api-tokens.copy')"
                                x-on:click="navigator.clipboard.writeText($refs.token.value); $flux.toast({{ Js::from(__('api/api-tokens.copied')) }})"
                            />
                        </x-slot:actions>
                        {{ $plainToken }}
                    </x-textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="primary">{{ __('common/buttons.close') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @elseif($showing)
            <form wire:submit="save" class="space-y-6">
                <flux:heading size="lg">{{ __('common/records.add', ['record' => __('api/api-tokens.api_token')]) }}</flux:heading>

                @if($scopes === [])
                    <flux:text>{{ __('api/api-tokens.no_declared_scopes') }}</flux:text>
                @else
                    <x-checkbox wire:model="form.scopes" :label="__('api/api-tokens.scopes')" :options="$scopes" />
                @endif

                <x-switch
                    wire:model="form.never_expires"
                    :label="__('api/api-tokens.never_expires')"
                    :description="__('api/api-tokens.expiry_description', ['days' => config('system.api.token_lifetime_days')])"
                />

                <flux:error name="tokens" />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('common/buttons.cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary">{{ __('api/api-tokens.issue') }}</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
