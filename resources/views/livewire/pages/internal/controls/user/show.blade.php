@use(App\Domain\User\Support\Impersonation)

<flux:main container>
    @isset($role)
        <x-breadcrumbs
            :items="[
                ['label' => __('permission/roles.title'), 'href' => route('internal.controls.roles.index')],
                ['label' => $role->label, 'href' => route('internal.controls.roles.show', $role)],
                ['label' => __('permission/roles.tabs.users'), 'href' => route('internal.controls.roles.show', ['role' => $role, 'tab' => 'users'])],
                ['label' => $user->email],
            ]"
            class="mb-6"
        />
    @else
        <x-breadcrumbs
            :items="[['label' => __('user/users.title'), 'href' => route('internal.controls.users.index')], ['label' => $user->email]]"
            class="mb-6"
        />
    @endisset

    <div class="mb-6 flex items-center gap-4">
        <flux:avatar size="lg" :name="$user->name" :initials="$user->initials()" />

        <div>
            <flux:heading size="xl" class="mb-0!">{{ $user->name }}</flux:heading>
            <flux:subheading>{{ $this->subheading }}</flux:subheading>
        </div>

        @if(Impersonation::allows(auth()->user(), $user))
            <flux:button
                class="ms-auto"
                icon="user-circle"
                data-test="impersonate-button"
                wire:click="$dispatchTo('pages.internal.controls.user.impersonate-modal', 'show', { id: {{ $user->id }} })"
            >
                {{ __('user/users.impersonation.action') }}
            </flux:button>
        @endif
    </div>

    @include('livewire.pages.internal.controls.user.partials.details', ['submit' => __('common/buttons.update')])

    <div class="mt-10">
        <livewire:pages.internal.controls.user.tabs :$user />
    </div>

    @if(Impersonation::allows(auth()->user(), $user))
        <livewire:pages.internal.controls.user.impersonate-modal :$role />
    @endif

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
