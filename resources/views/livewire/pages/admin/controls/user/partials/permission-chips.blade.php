@if($permissions !== [])
    <div @class(['flex flex-wrap gap-1.5 pb-2', $class])>
        @foreach($permissions as $permission)
            <button
                type="button"
                role="checkbox"
                x-show="isShown(@js($permission['name']))"
                x-bind:aria-checked="isGranted(@js($permission['name'])).toString()"
                x-bind:aria-disabled="isLocked(@js($permission['name'])).toString()"
                x-on:click="toggle(@js($permission['name']))"
                x-bind:class="{
                    'border-blue-300 bg-blue-50 dark:border-blue-500/50 dark:bg-blue-500/10': chipState(@js($permission['name'])) === 'on',
                    'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400': chipState(@js($permission['name'])) === 'lock',
                    'text-zinc-400 dark:text-zinc-500': readOnly && ! isGranted(@js($permission['name'])),
                    'cursor-pointer hover:border-zinc-300 dark:hover:border-zinc-600': ! isLocked(@js($permission['name'])),
                }"
                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-2 py-1 text-[13px] text-zinc-800 dark:border-zinc-700 dark:text-zinc-200"
                data-permission="{{ $permission['name'] }}"
            >
                <span
                    x-bind:class="{
                        'border-accent bg-accent text-accent-foreground': chipState(@js($permission['name'])) === 'on',
                        'border-zinc-300 bg-zinc-200 text-zinc-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300': chipState(@js($permission['name'])) === 'lock',
                        'opacity-45': readOnly,
                    }"
                    class="flex size-3.5 shrink-0 items-center justify-center rounded-sm border-[1.5px] border-zinc-400 dark:border-zinc-500"
                >
                    <flux:icon.check variant="micro" class="size-3" x-show="isGranted({{ Js::from($permission['name']) }})" />
                </span>

                <span x-html="highlighted(@js($permission['name']))">{{ $permission['label'] }}</span>

                @isset($fromRoles[$permission['name']])
                    <span class="rounded-md bg-zinc-200 px-1.5 text-[11px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                        {{ __('user/users.permissions.from', ['role' => $fromRoles[$permission['name']]]) }}
                    </span>
                @endisset
            </button>
        @endforeach
    </div>
@endif
