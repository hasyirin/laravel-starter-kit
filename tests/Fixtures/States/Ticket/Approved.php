<?php

declare(strict_types=1);

namespace Tests\Fixtures\States\Ticket;

class Approved extends TicketState
{
    public static string $name = 'approved';

    public function color(): string
    {
        return 'green';
    }
}
