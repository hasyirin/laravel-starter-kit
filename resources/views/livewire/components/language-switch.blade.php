<div>
    @if($languages->count() > 1)
        <nav @class(['flex items-center text-sm', 'justify-center gap-4' => ! $short, 'me-2 gap-2' => $short]) aria-label="{{ __('common/inputs.language') }}">
            @foreach($languages as $code => $name)
                @if($short && ! $loop->first)
                    <span class="text-zinc-400 dark:text-zinc-600" aria-hidden="true">|</span>
                @endif

                <button
                    type="button"
                    wire:click="switch('{{ $code }}')"
                    lang="{{ $code }}"
                    @class(['cursor-pointer', 'font-medium text-zinc-800 dark:text-white' => $code === $current, 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white' => $code !== $current])
                    @if($short) title="{{ $name }}" aria-label="{{ $name }}" @endif
                    @if($code === $current) aria-current="true" @endif
                >
                    {{ $labels[$code] }}
                </button>
            @endforeach
        </nav>
    @endif
</div>
