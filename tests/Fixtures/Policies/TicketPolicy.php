<?php

declare(strict_types=1);

namespace Tests\Fixtures\Policies;

use App\Domain\Shared\Common\Policies\Policy;

class TicketPolicy extends Policy
{
    public const array PERMISSIONS = [
        'in-review' => 'internal.tickets.in-review',
        'returned' => 'internal.tickets.returned',
        'approve' => 'internal.tickets.approve',
        'rejected' => 'internal.tickets.rejected',
    ];

    protected static string $prefix = 'internal';
}
