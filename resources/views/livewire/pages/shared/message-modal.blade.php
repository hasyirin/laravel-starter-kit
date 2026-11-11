@use(App\Domain\User\Support\Preferences)

<div>
    <flux:modal wire:model="showing" class="md:min-w-3xl">
        @if($showing && $message)
            @php
                $preferences = Preferences::current();
                $reason = $message->failure_reason;
                $copies = collect([...($message->cc ?? []), ...($message->bcc ?? [])]);
            @endphp

            <div class="space-y-6">
                <div class="space-y-2">
                    <flux:heading size="lg">{{ $message->subject ?? __('message/messages.no_subject') }}</flux:heading>

                    <flux:badge size="sm" :color="$message->status->color()">{{ $message->status->label() }}</flux:badge>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <x-entry :label="__('message/messages.recipient')">
                        {{ $message->recipient_name ? "$message->recipient_name <$message->recipient_address>" : $message->recipient_address }}
                    </x-entry>

                    @if($message->intercepted_to)
                        <x-entry :label="__('message/messages.intercepted_to')">{{ $message->intercepted_to }}</x-entry>
                    @endif

                    @if($message->cc)
                        <x-entry :label="__('message/messages.cc')">{{ collect($message->cc)->pluck('address')->join(', ') }}</x-entry>
                    @endif

                    @if($message->bcc)
                        <x-entry :label="__('message/messages.bcc')">{{ collect($message->bcc)->pluck('address')->join(', ') }}</x-entry>
                    @endif

                    <x-entry :label="__('message/messages.created_at')">{{ $preferences->datetime($message->created_at) }}</x-entry>

                    @if($message->sent_at)
                        <x-entry :label="__('message/messages.sent_at')">{{ $preferences->datetime($message->sent_at) }}</x-entry>
                    @endif

                    @if($message->resendOf)
                        <x-entry :label="__('message/messages.resend_of')">
                            <flux:link as="button" wire:click="show({{ $message->resendOf->id }})">
                                {{ $preferences->datetime($message->resendOf->created_at) }}
                            </flux:link>
                        </x-entry>
                    @endif
                </div>

                @if($reason)
                    <flux:callout variant="danger" icon="x-circle" :heading="$reason->label()">
                        <flux:callout.text>{{ $reason->suggestion() }}</flux:callout.text>
                        <flux:callout.text class="font-mono text-xs break-all">{{ $message->error['message'] ?? '' }}</flux:callout.text>
                    </flux:callout>
                @endif

                @if($message->tracked)
                    <div class="space-y-2">
                        <flux:heading>{{ __('message/messages.tracking') }}</flux:heading>

                        <div class="grid gap-6 sm:grid-cols-3">
                            <x-entry :label="__('message/messages.viewed')">
                                {{ $preferences->datetime($message->viewed_at) ?? __('message/messages.not_yet') }}
                            </x-entry>
                            <x-entry :label="__('message/messages.clicked')">
                                {{ $preferences->datetime($message->clicked_at) ?? __('message/messages.not_yet') }}
                            </x-entry>
                            <x-entry :label="__('message/messages.opened')">
                                @if($message->opened_at)
                                    {{ trans_choice('message/messages.opened_times', $message->open_count, ['time' => $preferences->datetime($message->opened_at), 'count' => $message->open_count]) }}
                                @else
                                    {{ __('message/messages.not_yet') }}
                                @endif
                            </x-entry>
                        </div>

                        <flux:text>{{ $copies->isEmpty() ? __('message/messages.signals') : __('message/messages.signals_with_copies') }}</flux:text>
                    </div>
                @endif

                @if($message->media->isNotEmpty())
                    <div class="space-y-2">
                        <flux:heading>{{ __('message/messages.attachments') }}</flux:heading>

                        <div class="flex flex-wrap gap-2">
                            @foreach($message->media as $attachment)
                                <flux:button
                                    size="sm"
                                    icon="paper-clip"
                                    :href="route('media.show', $attachment)"
                                    target="_blank"
                                    wire:key="attachment-{{ $attachment->id }}"
                                >
                                    {{ $attachment->file_name }}
                                </flux:button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="space-y-2">
                    <flux:heading>{{ __('message/messages.body') }}</flux:heading>

                    @if($message->html_body)
                        <iframe
                            sandbox
                            srcdoc="{{ $message->html_body }}"
                            title="{{ __('message/messages.body') }}"
                            class="h-96 w-full rounded-lg border border-zinc-200 bg-white dark:border-white/10"
                        ></iframe>
                    @elseif($message->text_body)
                        <pre
                            class="max-h-96 overflow-auto rounded-lg border border-zinc-200 p-4 text-sm whitespace-pre-wrap dark:border-white/10"
                            >{{ $message->text_body }}</pre
                        >
                    @else
                        <flux:text>{{ __('message/messages.no_body') }}</flux:text>
                    @endif
                </div>

                @can('resend', $message)
                    <form wire:submit="resend" class="space-y-6">
                        @if($message->channel === 'sms')
                            <x-input.phone wire:model="address" :label="__('message/messages.resend_to')" required />
                        @else
                            <x-input wire:model="address" name="address" type="email" :label="__('message/messages.resend_to')" required />
                        @endif

                        <div class="flex justify-end gap-2">
                            <flux:button type="submit" variant="primary">{{ __('message/messages.resend') }}</flux:button>
                        </div>
                    </form>
                @endcan
            </div>
        @endif
    </flux:modal>
</div>
