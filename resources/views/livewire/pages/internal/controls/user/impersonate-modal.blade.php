<div>
    <flux:modal wire:model="showing" class="max-w-md">
        @if($showing)
            <form wire:submit="impersonate" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('user/users.impersonation.heading', ['name' => $user->name]) }}</flux:heading>

                    <flux:subheading>{{ __('user/users.impersonation.description') }}</flux:subheading>
                </div>

                <x-input.password wire:model="password" :label="__('common/inputs.password')" required autocomplete="current-password" />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('common/buttons.cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button variant="primary" type="submit">{{ __('user/users.impersonation.action') }}</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
