<flux:main container>
    <x-breadcrumbs :items="[['label' => __('user/users.title'), 'href' => route('admin.controls.users.index')], ['label' => $user->email]]" class="mb-6" />

    <flux:heading size="xl" class="mb-6">{{ $user->name }}</flux:heading>

    @include('livewire.pages.admin.controls.user.partials.details', ['submit' => __('common/buttons.update')])

    <div class="mt-6">
        <livewire:pages.admin.controls.user.tabs :user-id="$user->id" />
    </div>

    @can('update', $user)
        @unless($user->hasVerifiedEmail())
            <flux:modal name="verify-email" class="max-w-md">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('user/users.verify.heading') }}</flux:heading>

                        <flux:subheading>{{ __('user/users.verify.description', ['email' => $user->email]) }}</flux:subheading>
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="filled">{{ __('common/buttons.cancel') }}</flux:button>
                        </flux:modal.close>

                        <flux:button variant="primary" wire:click="verifyEmail">{{ __('common/buttons.confirm') }}</flux:button>
                    </div>
                </div>
            </flux:modal>
        @endunless
    @endcan
</flux:main>
