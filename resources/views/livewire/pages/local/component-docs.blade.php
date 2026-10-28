<div class="mx-auto flex min-h-screen max-w-7xl flex-col md:flex-row">
    <aside
        class="border-b border-zinc-200 p-6 md:sticky md:top-0 md:h-screen md:w-64 md:shrink-0 md:overflow-y-auto md:border-e md:border-b-0 dark:border-zinc-700"
    >
        <flux:heading size="lg">{{ __('docs.components') }}</flux:heading>

        <flux:navlist class="mt-4">
            <flux:navlist.item :href="route('docs.components')" :current="$page === null" wire:navigate>{{ __('docs.overview') }}</flux:navlist.item>

            @foreach($documents as $name => $title)
                <flux:navlist.item :href="route('docs.components', $name)" :current="$page === $name" wire:key="document-{{ $name }}" wire:navigate>
                    {{ $title }}
                </flux:navlist.item>
            @endforeach
        </flux:navlist>
    </aside>

    <main class="min-w-0 flex-1 px-6 py-10 md:px-12">
        <article class="mx-auto max-w-3xl">
            @foreach($parts as $part)
                @isset($part['preview'])
                    <div
                        class="my-6 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700"
                        wire:key="example-{{ $loop->index }}"
                        data-docs-example
                    >
                        <div class="grid gap-6 bg-white p-6 dark:bg-zinc-900">{{ $part['preview']() }}</div>

                        @if($part['bindings'])
                            <div
                                class="flex flex-wrap gap-x-6 gap-y-1 border-t border-zinc-200 bg-zinc-50 px-6 py-2 font-mono text-xs dark:border-zinc-700 dark:bg-zinc-800"
                            >
                                @foreach($part['bindings'] as $binding)
                                    <span wire:key="binding-{{ $loop->parent->index }}-{{ $loop->index }}">
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ $binding }}</span>
                                        <span class="text-zinc-800 dark:text-zinc-100" x-text="JSON.stringify($wire.$get(@js($binding)) ?? '')"></span>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex items-start border-t border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-950" x-data>
                            <pre
                                class="min-w-0 flex-1 overflow-x-auto p-4 font-mono text-xs leading-relaxed text-zinc-800 [font-variant-ligatures:none] dark:text-zinc-200"
                            ><code x-ref="code">{{ $part['code'] }}</code></pre>
                            <div class="shrink-0 p-2">
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    icon="clipboard"
                                    :aria-label="__('docs.copy')"
                                    x-on:click="navigator.clipboard.writeText($refs.code.textContent); $flux.toast({{ Js::from(__('docs.copied')) }})"
                                />
                            </div>
                        </div>
                    </div>
                @else
                    <div
                        class="[&_a]:font-medium [&_a]:text-(--color-accent-content) [&_a]:underline [&_a]:underline-offset-4 [&_code]:rounded [&_code]:bg-zinc-200/60 [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:[font-variant-ligatures:none] [&_code]:text-[0.8125em] [&_code]:text-zinc-800 dark:[&_code]:bg-white/10 dark:[&_code]:text-zinc-200 [&_h1]:text-2xl [&_h1]:font-semibold [&_h1]:text-zinc-900 dark:[&_h1]:text-white [&_h2]:mt-12 [&_h2]:mb-3 [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:text-zinc-900 dark:[&_h2]:text-white [&_h3]:mt-8 [&_h3]:mb-2 [&_h3]:font-semibold [&_h3]:text-zinc-900 dark:[&_h3]:text-white [&_li]:my-1 [&_p]:my-3 [&_pre]:my-4 [&_pre]:overflow-x-auto [&_pre]:rounded-xl [&_pre]:bg-zinc-50 [&_pre]:p-4 [&_pre]:text-xs dark:[&_pre]:bg-zinc-950 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_table]:my-4 [&_table]:w-full [&_td]:border-b [&_td]:border-zinc-200 [&_td]:py-2 [&_td]:pe-4 [&_td]:align-top dark:[&_td]:border-zinc-800 [&_th]:border-b [&_th]:border-zinc-300 [&_th]:py-2 [&_th]:pe-4 [&_th]:text-start [&_th]:font-medium [&_th]:text-zinc-900 dark:[&_th]:border-zinc-700 dark:[&_th]:text-white [&_ul]:my-3 [&_ul]:list-disc [&_ul]:ps-5 overflow-x-auto text-sm leading-6 text-zinc-600 dark:text-zinc-300"
                    >
                        {{ $part['html'] }}
                    </div>
                @endisset
            @endforeach
        </article>
    </main>
</div>
