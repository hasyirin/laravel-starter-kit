@use(App\Domain\Shared\Common\Support\AuditLog)
@use(App\Domain\User\Support\Preferences)

<div>
    <flux:modal wire:model="showing" class="md:min-w-3xl">
        @if($showing && $apiCall)
            @php
                $bodies = collect(['request_body', 'response_body'])->filter(fn (string $body): bool => filled($apiCall->{$body}));
            @endphp

            <div class="space-y-6">
                <div class="space-y-2">
                    <flux:heading size="lg" class="break-all">{{ $apiCall->method }} {{ $apiCall->url }}</flux:heading>

                    <div class="flex flex-wrap gap-2">
                        <flux:badge size="sm" :color="$apiCall->outcome->color()">{{ $apiCall->outcome->label() }}</flux:badge>
                        <flux:badge size="sm">{{ $apiCall->direction->label() }}</flux:badge>
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <x-entry :label="__('api/api-calls.time')">{{ Preferences::current()->datetime($apiCall->created_at) }}</x-entry>

                    @if($apiCall->status !== null)
                        <x-entry :label="__('api/api-calls.status')">{{ $apiCall->status }}</x-entry>
                    @endif

                    <x-entry :label="__('api/api-calls.duration')">{{ __('api/api-calls.duration_ms', ['duration' => $apiCall->duration_ms]) }}</x-entry>

                    @if($apiCall->service)
                        <x-entry :label="__('api/api-calls.service')">{{ $apiCall->service }}</x-entry>
                    @endif

                    @if($apiCall->api_client_name)
                        <x-entry :label="__('api/api-calls.api_client')">{{ $apiCall->api_client_name }}</x-entry>
                    @endif

                    @if($apiCall->ip_address)
                        <x-entry :label="__('api/api-calls.ip_address')">{{ $apiCall->ip_address }}</x-entry>
                    @endif

                    @if($apiCall->user_agent)
                        <x-entry :label="__('api/api-calls.user_agent')" class="break-all">{{ $apiCall->user_agent }}</x-entry>
                    @endif

                    @if($apiCall->trace_id)
                        <x-entry :label="__('api/api-calls.trace_id')">{{ $apiCall->trace_id }}</x-entry>
                    @endif

                    @if($apiCall->route_name)
                        <x-entry :label="__('api/api-calls.route_name')">{{ $apiCall->route_name }}</x-entry>
                    @endif

                    @if($apiCall->request_size !== null)
                        <x-entry :label="__('api/api-calls.request_size')">
                            {{ trans_choice('api/api-calls.size_bytes', $apiCall->request_size, ['size' => $apiCall->request_size]) }}
                        </x-entry>
                    @endif

                    @if($apiCall->response_size !== null)
                        <x-entry :label="__('api/api-calls.response_size')">
                            {{ trans_choice('api/api-calls.size_bytes', $apiCall->response_size, ['size' => $apiCall->response_size]) }}
                        </x-entry>
                    @endif

                    @if($apiCall->apiToken)
                        <x-entry :label="__('api/api-calls.api_token')">
                            {{ __('api/api-calls.token_ending', ['last_four' => $apiCall->apiToken->last_four]) }}
                        </x-entry>
                    @endif

                    @if($apiCall->resource_type)
                        <x-entry :label="__('api/api-calls.resource')">{{ AuditLog::typeLabel($apiCall->resource_type) }} #{{ $apiCall->resource_id }}</x-entry>
                    @endif
                </div>

                @if($apiCall->error)
                    <flux:callout variant="danger" icon="x-circle" :heading="__('api/api-calls.error')">
                        <flux:callout.text class="font-mono text-xs break-all">{{ $apiCall->error }}</flux:callout.text>
                    </flux:callout>
                @endif

                @foreach($bodies as $body)
                    <div class="space-y-2" wire:key="{{ $body }}">
                        <flux:heading>{{ __("api/api-calls.$body") }}</flux:heading>

                        <pre
                            class="max-h-96 overflow-auto rounded-lg border border-zinc-200 p-4 text-sm break-all whitespace-pre-wrap dark:border-white/10"
                            >{{ $apiCall->{$body} }}</pre
                        >
                    </div>
                @endforeach
            </div>
        @endif
    </flux:modal>
</div>
