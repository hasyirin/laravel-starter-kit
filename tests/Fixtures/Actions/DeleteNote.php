<?php

declare(strict_types=1);

namespace Tests\Fixtures\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use Tests\Fixtures\Models\Note;

class DeleteNote
{
    use Actionable;

    public function execute(Note $note): void
    {
        $note->update(['title' => 'Deleted by an Action']);
        $note->delete();
    }
}
