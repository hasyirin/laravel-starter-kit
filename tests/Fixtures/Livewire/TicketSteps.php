<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;
use Tests\Fixtures\Models\Ticket;

class TicketSteps extends Component
{
    public int $ticketId = 0;

    public ?bool $ran = null;

    public function move(string $state): void
    {
        $this->ran = $this->transitionTo(Ticket::query()->findOrFail($this->ticketId)->state, $state);
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
