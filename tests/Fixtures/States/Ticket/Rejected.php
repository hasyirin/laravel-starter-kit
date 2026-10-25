<?php

declare(strict_types=1);

namespace Tests\Fixtures\States\Ticket;

class Rejected extends TicketState
{
    public static string $name = 'rejected';

    public function label(): string
    {
        return 'Turned Down';
    }
}
