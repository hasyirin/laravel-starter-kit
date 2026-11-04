@use(App\Domain\User\Models\Profile)
@use(App\Domain\Shared\Common\Support\Activities)

<div>
    <flux:modal wire:model="open" class="md:min-w-xl">
        @if($open && $this->activity)
            @php($activity = $this->activity)
            @php($changes = Activities::changes($activity))

            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ Activities::subjectLabel($activity) }}</flux:heading>

                    <flux:subheading>{{ Activities::summary($activity) }}</flux:subheading>
                </div>

                @if($changes->isEmpty())
                    <flux:text>{{ __('common/audit.no_changes') }}</flux:text>
                @else
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('common/audit.field') }}</flux:table.column>
                            <flux:table.column>{{ __('common/audit.old') }}</flux:table.column>
                            <flux:table.column>{{ __('common/audit.new') }}</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach($changes as $change)
                                <flux:table.row wire:key="change-{{ $loop->index }}">
                                    <flux:table.cell>{{ $change['field'] }}</flux:table.cell>
                                    <flux:table.cell class="break-all whitespace-normal">{{ $change['old'] }}</flux:table.cell>
                                    <flux:table.cell class="break-all whitespace-normal">{{ $change['new'] }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif

                @if($activity->subject instanceof Profile)
                    <div class="space-y-2">
                        <flux:heading>{{ __('user/users.roles') }}</flux:heading>

                        <div class="flex flex-wrap gap-2">
                            @forelse($activity->subject->roles as $role)
                                <flux:badge size="sm" wire:key="role-{{ $role->id }}">{{ $role->label }}</flux:badge>
                            @empty
                                <flux:text>{{ __('common/audit.none') }}</flux:text>
                            @endforelse
                        </div>
                    </div>

                    <div class="space-y-2">
                        <flux:heading>{{ __('user/users.permissions.heading') }}</flux:heading>

                        <div class="flex flex-wrap gap-2">
                            @forelse($activity->subject->permissions as $permission)
                                <flux:badge size="sm" wire:key="permission-{{ $permission->id }}">{{ $permission->name }}</flux:badge>
                            @empty
                                <flux:text>{{ __('common/audit.none') }}</flux:text>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </flux:modal>
</div>
