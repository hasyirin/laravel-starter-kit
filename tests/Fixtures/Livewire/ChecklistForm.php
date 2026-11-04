<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Form;
use Livewire\Attributes\Locked;
use Tests\Fixtures\Actions\SaveChecklist;
use Tests\Fixtures\Models\Note;
use Tests\Fixtures\Models\Notebook;

class ChecklistForm extends Form
{
    #[Locked]
    public Notebook $notebook;

    #[Locked]
    public Note $note;

    public string $title = '';

    public function set(Notebook $notebook, Note $note): static
    {
        $this->notebook = $notebook;
        $this->note = $note;
        $this->title = $note->title ?? '';

        return $this;
    }

    public function save(): Note
    {
        $this->validate();

        return SaveChecklist::make()->execute($this->notebook, $this->note, $this->title);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['title' => ['required']];
    }
}
