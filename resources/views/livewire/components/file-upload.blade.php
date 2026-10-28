<div class="grid gap-2">
    @unless($disabled || $full)
        @php
            $dropzoneHeading = $heading ?? trans_choice('common/uploads.heading', $multiple ? 2 : 1);
        @endphp

        <flux:file-upload wire:model="uploads" :$multiple :accept="$acceptedTypes">
            {{-- The dropzone opens the file picker with a click, Enter, or Space. Its own text would also read out the hidden progress. --}}
            <flux:file-upload.dropzone
                :heading="$dropzoneHeading"
                :text="$limits"
                :$inline
                with-progress
                role="button"
                :aria-label="trim($dropzoneHeading.' '.$limits)"
            />
        </flux:file-upload>
    @endunless

    <flux:error name="uploads" />

    @if($items !== [])
        <div class="grid gap-2" @if($multiple && ! $disabled) wire:sort="sort" @endif>
            @foreach($items as $item)
                @php
                    $stored = ! isset($item['upload']);
                    $preview = $previews[$item['uuid']] ?? null;
                @endphp

                <div wire:key="{{ $item['uuid'] }}" wire:sort:item="{{ $item['uuid'] }}">
                    <flux:file-item :heading="$item['name']" :size="$item['size']" :image="$preview">
                        @if($stored || ! $disabled)
                            <x-slot name="actions">
                                @if($stored)
                                    <flux:button
                                        :href="route('media.show', $item['uuid'])"
                                        :target="$preview === null ? null : '_blank'"
                                        :icon="$preview === null ? 'arrow-down-tray' : 'eye'"
                                        :aria-label="__($preview === null ? 'common/uploads.download' : 'common/uploads.open', ['name' => $item['name']])"
                                        variant="subtle"
                                        size="sm"
                                        square
                                    />
                                @endif

                                @unless($disabled)
                                    <flux:file-item.remove
                                        wire:click="remove(@js($item['uuid']))"
                                        :aria-label="__('common/uploads.remove', ['name' => $item['name']])"
                                    />
                                @endunless
                            </x-slot>
                        @endif
                    </flux:file-item>
                </div>
            @endforeach
        </div>
    @elseif($disabled)
        <flux:text>{{ __('common/uploads.none') }}</flux:text>
    @endif
</div>
