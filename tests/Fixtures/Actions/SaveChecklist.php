<?php

declare(strict_types=1);

namespace Tests\Fixtures\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use Tests\Fixtures\Models\Note;
use Tests\Fixtures\Models\Notebook;

class SaveChecklist
{
    use Actionable;

    public function execute(Notebook $notebook, Note $note, string $title): Note
    {
        $note->notebook()->associate($notebook);
        $note->type = 'checklist';
        $note->title = $title;
        $note->save();

        return $note;
    }
}
