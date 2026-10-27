<?php

declare(strict_types=1);

namespace Tests\Fixtures\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use Tests\Fixtures\Models\Ticket;

class MoveTicket
{
    use Actionable;

    public function execute(Ticket $ticket, string $state): void
    {
        $ticket->update(['title' => 'Moved by an Action']);
        $ticket->state->transitionTo($state);
    }
}
