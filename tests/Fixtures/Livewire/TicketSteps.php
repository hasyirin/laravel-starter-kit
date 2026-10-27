<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;
use Carbon\CarbonImmutable;
use RuntimeException;
use Tests\Fixtures\Actions\MoveTicket;
use Tests\Fixtures\Models\Ticket;
use Tests\Fixtures\States\Ticket\Approved;

class TicketSteps extends Component
{
    public int $ticketId = 0;

    public ?bool $ran = null;

    public function move(string $state): void
    {
        $this->ran = $this->transitionTo(Ticket::query()->findOrFail($this->ticketId)->state, $state);
    }

    public function approveOn(string $date): void
    {
        $this->ran = $this->transitionTo(Ticket::query()->findOrFail($this->ticketId)->state, Approved::class, CarbonImmutable::parse($date));
    }

    public function moveWithAction(string $state): void
    {
        MoveTicket::make()->execute(Ticket::query()->findOrFail($this->ticketId), $state);

        $this->ran = true;
    }

    public function fail(): void
    {
        throw new RuntimeException('Not a step.');
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
