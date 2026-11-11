@use(App\Domain\User\Support\Preferences)

<div>
    <flux:modal wire:model="showing" class="md:min-w-3xl">
        @if($showing && $mail)
            @php
                $preferences = Preferences::current();
                $reason = $mail->failure_reason;
                $copies = collect([...($mail->cc ?? []), ...($mail->bcc ?? [])]);
            @endphp

            <div class="space-y-6">
                <div class="space-y-2">
                    <flux:heading size="lg">{{ $mail->subject ?? __('mail/mails.no_subject') }}</flux:heading>

                    <flux:badge size="sm" :color="$mail->status->color()">{{ $mail->status->label() }}</flux:badge>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <x-entry :label="__('mail/mails.recipient')">
                        {{ $mail->recipient_name ? "$mail->recipient_name <$mail->recipient_email>" : $mail->recipient_email }}
                    </x-entry>

                    @if($mail->intercepted_to)
                        <x-entry :label="__('mail/mails.intercepted_to')">{{ $mail->intercepted_to }}</x-entry>
                    @endif

                    @if($mail->cc)
                        <x-entry :label="__('mail/mails.cc')">{{ collect($mail->cc)->pluck('address')->join(', ') }}</x-entry>
                    @endif

                    @if($mail->bcc)
                        <x-entry :label="__('mail/mails.bcc')">{{ collect($mail->bcc)->pluck('address')->join(', ') }}</x-entry>
                    @endif

                    <x-entry :label="__('mail/mails.created_at')">{{ $preferences->datetime($mail->created_at) }}</x-entry>

                    @if($mail->sent_at)
                        <x-entry :label="__('mail/mails.sent_at')">{{ $preferences->datetime($mail->sent_at) }}</x-entry>
                    @endif

                    @if($mail->resendOf)
                        <x-entry :label="__('mail/mails.resend_of')">
                            <flux:link as="button" wire:click="show({{ $mail->resendOf->id }})">
                                {{ $preferences->datetime($mail->resendOf->created_at) }}
                            </flux:link>
                        </x-entry>
                    @endif
                </div>

                @if($reason)
                    <flux:callout variant="danger" icon="x-circle" :heading="$reason->label()">
                        <flux:callout.text>{{ $reason->suggestion() }}</flux:callout.text>
                        <flux:callout.text class="font-mono text-xs break-all">{{ $mail->error['message'] ?? '' }}</flux:callout.text>
                    </flux:callout>
                @endif

                @if($mail->tracked)
                    <div class="space-y-2">
                        <flux:heading>{{ __('mail/mails.tracking') }}</flux:heading>

                        <div class="grid gap-6 sm:grid-cols-3">
                            <x-entry :label="__('mail/mails.viewed')">{{ $preferences->datetime($mail->viewed_at) ?? __('mail/mails.not_yet') }}</x-entry>
                            <x-entry :label="__('mail/mails.clicked')">{{ $preferences->datetime($mail->clicked_at) ?? __('mail/mails.not_yet') }}</x-entry>
                            <x-entry :label="__('mail/mails.opened')">
                                @if($mail->opened_at)
                                    {{ trans_choice('mail/mails.opened_times', $mail->open_count, ['time' => $preferences->datetime($mail->opened_at), 'count' => $mail->open_count]) }}
                                @else
                                    {{ __('mail/mails.not_yet') }}
                                @endif
                            </x-entry>
                        </div>

                        <flux:text>{{ $copies->isEmpty() ? __('mail/mails.signals') : __('mail/mails.signals_with_copies') }}</flux:text>
                    </div>
                @endif

                @if($mail->media->isNotEmpty())
                    <div class="space-y-2">
                        <flux:heading>{{ __('mail/mails.attachments') }}</flux:heading>

                        <div class="flex flex-wrap gap-2">
                            @foreach($mail->media as $attachment)
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
                    <flux:heading>{{ __('mail/mails.body') }}</flux:heading>

                    @if($mail->html_body)
                        <iframe
                            sandbox
                            srcdoc="{{ $mail->html_body }}"
                            title="{{ __('mail/mails.body') }}"
                            class="h-96 w-full rounded-lg border border-zinc-200 bg-white dark:border-white/10"
                        ></iframe>
                    @elseif($mail->text_body)
                        <pre
                            class="max-h-96 overflow-auto rounded-lg border border-zinc-200 p-4 text-sm whitespace-pre-wrap dark:border-white/10"
                            >{{ $mail->text_body }}</pre
                        >
                    @else
                        <flux:text>{{ __('mail/mails.no_body') }}</flux:text>
                    @endif
                </div>

                @can('resend', $mail)
                    <form wire:submit="resend" class="space-y-6">
                        <x-input wire:model="email" name="email" type="email" case="lower" :label="__('mail/mails.resend_to')" required />

                        <div class="flex justify-end gap-2">
                            <flux:button type="submit" variant="primary">{{ __('mail/mails.resend') }}</flux:button>
                        </div>
                    </form>
                @endcan
            </div>
        @endif
    </flux:modal>
</div>
