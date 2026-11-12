<div>
    <flux:modal wire:model="showing" class="md:w-lg">
        @if($showing && $plainToken !== null)
            <div class="space-y-6">
                <flux:heading size="lg">{{ __('api/api-tokens.issued_heading') }}</flux:heading>

                <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('api/api-tokens.issued_warning')" />

                <x-input :value="$plainToken" :label="__('api/api-tokens.api_token')" input-class="font-mono" readonly copyable />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="primary">{{ __('common/buttons.close') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @elseif($showing && $apiToken !== null)
            <form wire:submit="save" class="space-y-6">
                <flux:heading size="lg">{{ __('api/api-tokens.rotate_heading') }}</flux:heading>

                <flux:text>
                    {{ __('api/api-tokens.rotate_description', ['ending' => __('api/api-tokens.ending', ['last_four' => $apiToken->last_four]),'days' => config('system.api.rotation_overlap_days')]) }}
                </flux:text>

                <flux:error name="tokens" />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('common/buttons.cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary">{{ __('api/api-tokens.rotate') }}</flux:button>
                </div>
            </form>
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
