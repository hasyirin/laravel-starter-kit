@use(App\Domain\Shared\Common\Models\AuditEntry)
@use(App\Domain\User\Models\Profile)
@use(App\Domain\Shared\Common\Support\AuditLog)

<div>
    <flux:modal wire:model="showing" class="md:min-w-xl">
        @if($showing && $entry)
            @php($changes = AuditLog::changes($entry))
            @php($impersonator = AuditLog::impersonatorName($entry))

            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $entry->subject_type ? AuditLog::subjectLabel($entry) : AuditLog::eventLabel($entry) }}</flux:heading>

                    <flux:subheading>{{ AuditLog::summary($entry) }}</flux:subheading>

                    @if($impersonator)
                        <flux:subheading>{{ __('user/users.impersonation.by', ['name' => $impersonator]) }}</flux:subheading>
                    @endif
                </div>

                @if($entry instanceof AuditEntry && $entry->event === 'printed-manual-letter')
                    <div class="space-y-2">
                        <flux:heading>{{ __('common/audit.letter.type') }}</flux:heading>

                        <flux:text>{{ $entry->getProperty('type') }}</flux:text>
                    </div>

                    @foreach(['data', 'extra', 'details'] as $property)
                        @php($values = AuditLog::values($entry, $property))

                        @if($values->isNotEmpty())
                            <div class="space-y-2" wire:key="values-{{ $property }}">
                                <flux:heading>{{ __("common/audit.letter.$property") }}</flux:heading>

                                <dl class="grid grid-cols-[max-content_1fr] gap-x-4 gap-y-1 text-sm">
                                    @foreach($values as $label => $value)
                                        <dt class="font-medium">{{ $label }}</dt>
                                        <dd class="break-all">{{ $value }}</dd>
                                    @endforeach
                                </dl>
                            </div>
                        @endif
                    @endforeach

                    @if($entry->media->isNotEmpty())
                        <div class="space-y-2">
                            <flux:heading>{{ __('common/audit.letter.images') }}</flux:heading>

                            <div class="flex flex-wrap gap-4">
                                @foreach($entry->media as $media)
                                    <a href="{{ route('media.show', $media) }}" target="_blank" class="space-y-1 text-sm" wire:key="media-{{ $media->id }}">
                                        @if($media->hasGeneratedConversion('preview'))
                                            <img
                                                src="{{ route('media.show', [$media, 'preview']) }}"
                                                alt="{{ $media->file_name }}"
                                                class="max-h-32 rounded border"
                                            />
                                        @endif
                                        <span class="block">{{ $media->file_name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

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

                @if($entry->subject instanceof Profile)
                    <div class="space-y-2">
                        <flux:heading>{{ __('user/users.roles') }}</flux:heading>

                        <div class="flex flex-wrap gap-2">
                            @forelse($entry->subject->roles as $role)
                                <flux:badge size="sm" wire:key="role-{{ $role->id }}">{{ $role->label }}</flux:badge>
                            @empty
                                <flux:text>{{ __('common/audit.none') }}</flux:text>
                            @endforelse
                        </div>
                    </div>

                    <div class="space-y-2">
                        <flux:heading>{{ __('user/users.permissions.heading') }}</flux:heading>

                        <div class="flex flex-wrap gap-2">
                            @forelse($entry->subject->permissions as $permission)
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
