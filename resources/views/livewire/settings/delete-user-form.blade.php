<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <flux:heading>{{ __('user/settings.delete_account.heading') }}</flux:heading>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button variant="danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
            {{ __('user/settings.delete_account.button') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('user/settings.delete_account.confirm.heading') }}</flux:heading>

                <flux:subheading>{{ __('user/settings.delete_account.confirm.description') }}</flux:subheading>
            </div>

            <flux:input wire:model="password" :label="__('common/inputs.password')" type="password" viewable />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('common/buttons.cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit">{{ __('user/settings.delete_account.submit') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
