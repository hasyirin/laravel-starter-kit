<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\RecordModal;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Locked;
use Tests\Fixtures\Models\Note;
use Tests\Fixtures\Models\Notebook;

/**
 * @extends RecordModal<Note>
 */
class ChecklistModal extends RecordModal
{
    #[Locked]
    public Notebook $notebook;

    public ChecklistForm $form;

    public static function label(): string
    {
        return 'Checklist';
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
        $this->form->set($this->notebook, $this->record);
    }

    protected function persist(): void
    {
        $this->form->save();
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <flux:modal wire:model="showing">
                    @if ($showing)
                        <form wire:submit="save">
                            <input wire:model="form.title" />
                        </form>
                    @endif
                </flux:modal>
            </div>
            BLADE;
    }
}
