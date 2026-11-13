<div>
    @if($languages->count() > 1)
        <nav class="flex justify-center gap-4 text-sm" aria-label="{{ __('common/inputs.language') }}">
            @foreach($languages as $code => $name)
                <button
                    type="button"
                    wire:click="switch('{{ $code }}')"
                    lang="{{ $code }}"
                    @class(['cursor-pointer', 'font-medium text-zinc-800 dark:text-white' => $code === $current, 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white' => $code !== $current])
                    @if($code === $current) aria-current="true" @endif
                >
                    {{ $name }}
                </button>
            @endforeach
        </nav>
    @endif
</div>
