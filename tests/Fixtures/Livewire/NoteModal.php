<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\RecordModal;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Tests\Fixtures\Models\Note;
use Tests\Fixtures\Models\Notebook;

/**
 * @extends RecordModal<Note>
 */
class NoteModal extends RecordModal
{
    #[Locked]
    public Notebook $notebook;

    #[Validate('required')]
    public string $title = '';

    public static function label(): string
    {
        return 'Note';
    }

    /**
     * @return HasMany<Note, Notebook>
     */
    protected function query(): HasMany
    {
        return $this->notebook->notes();
    }

    protected function fillForm(): void
    {
        $this->title = $this->record->title ?? '';
    }

    protected function persist(): void
    {
        $this->record->fill(['title' => $this->title])->save();
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <flux:modal wire:model="open" data-note-modal>
                    @if ($open)
                        <form wire:submit="save" data-note-form>
                            <input wire:model="title" data-note-title />
                            @error('title') <p data-note-error>{{ $message }}</p> @enderror
                            @unless ($disabled)
                                <button type="submit" data-note-save>{{ __('common/buttons.save') }}</button>
                            @endunless
                        </form>
                    @endif
                </flux:modal>
            </div>
            BLADE;
    }
}
