@php
    $key = $node['key'];
    $resource = $node['kind'] === 'resource';
@endphp

<div x-show="nodeShown(@js($key))" data-node="{{ $key }}">
    <div class="flex items-center gap-2 py-1.5">
        <button
            type="button"
            x-on:click="toggleOpen(@js($key))"
            x-bind:aria-expanded="opened(@js($key)).toString()"
            x-bind:aria-label="opened(@js($key)) ? @js(__('user/users.permissions.collapse', ['label' => $node['label']])) : @js(__('user/users.permissions.expand', ['label' => $node['label']]))"
            class="flex size-3.5 shrink-0 cursor-pointer items-center justify-center text-zinc-500 dark:text-zinc-400"
        >
            <flux:icon.chevron-right variant="micro" class="size-3.5 transition-transform" x-bind:class="opened({{ Js::from($key) }}) && 'rotate-90'" />
        </button>

        @if($resource)
            <button
                type="button"
                role="checkbox"
                x-bind:aria-checked="resourceChecked(@js($key))"
                x-bind:aria-disabled="readOnly.toString()"
                x-on:click="toggleResource(@js($key))"
                x-bind:class="{
                    'border-accent bg-accent text-accent-foreground': resourceState(@js($key)) !== 'off',
                    'opacity-45': readOnly,
                    'cursor-pointer': ! readOnly,
                }"
                aria-label="{{ $node['label'] }}"
                class="flex size-3.5 shrink-0 items-center justify-center rounded-sm border-[1.5px] border-zinc-400 dark:border-zinc-500"
                data-resource-box
            >
                <flux:icon.check variant="micro" class="size-3" x-show="resourceState({{ Js::from($key) }}) === 'on'" />
                <flux:icon.minus variant="micro" class="size-3" x-show="resourceState({{ Js::from($key) }}) === 'part'" />
            </button>
        @endif

        <span
            x-on:click="toggleOpen(@js($key))"
            @class([
                'cursor-pointer font-semibold',
                'text-zinc-800 dark:text-zinc-100' => $resource,
                'text-zinc-600 dark:text-zinc-300' => ! $resource,
            ])
        >
            {{ $node['label'] }}
        </span>

        <span class="ms-auto text-xs text-zinc-500 dark:text-zinc-400" x-text="countText(@js($key))"></span>
    </div>

    @if($resource)
        <div x-show="opened(@js($key))">
            @include('livewire.pages.internal.controls.partials.permission-chips', ['permissions' => $node['permissions'], 'class' => 'ps-5.5'])
        </div>
    @else
        <div x-show="opened(@js($key))" class="ms-1.5 border-s border-zinc-200 ps-4 dark:border-zinc-700">
            @include('livewire.pages.internal.controls.partials.permission-chips', ['permissions' => $node['permissions'], 'class' => 'ps-5.5'])

            @foreach($node['children'] as $child)
                @include('livewire.pages.internal.controls.partials.permission-node', ['node' => $child])
            @endforeach
        </div>
    @endif
</div>
