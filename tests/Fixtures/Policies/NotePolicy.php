<?php

declare(strict_types=1);

namespace Tests\Fixtures\Policies;

use App\Domain\User\Models\User;
use Tests\Fixtures\Models\Note;

class NotePolicy
{
    public function view(User $user, Note $note): bool
    {
        return ! $note->hidden;
    }

    public function create(User $user, Note $note): bool
    {
        return $note->notebook->accepts_notes;
    }

    public function update(User $user, Note $note): bool
    {
        return ! $note->locked;
    }
}
