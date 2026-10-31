<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Tests\Fixtures\Models\Notebook;

#[Layout('layouts.guest')]
class NoteModalPage extends Component
{
    public Notebook $notebook;

    public int $saves = 0;

    public function mount(): void
    {
        $this->notebook = Notebook::query()->firstOrFail();
    }

    #[On('Tests\Fixtures\Livewire\NoteModal:saved')]
    public function countSave(): void
    {
        $this->saves++;
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <button data-open wire:click="$dispatchTo('note-modal', 'show')">Add</button>
                <span data-saves>{{ $saves }}</span>
                <livewire:note-modal :notebook="$notebook" />
            </div>
            BLADE;
    }
}
