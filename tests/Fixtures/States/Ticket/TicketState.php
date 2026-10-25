<?php

declare(strict_types=1);

namespace Tests\Fixtures\States\Ticket;

use App\Domain\Shared\Common\States\State;
use Spatie\ModelStates\Attributes\DefaultState;
use Spatie\ModelStates\StateConfig;
use Tests\Fixtures\Models\Ticket;
use Tests\Fixtures\Transitions\Approve;

/**
 * @extends State<Ticket>
 */
#[DefaultState(Open::class)]
abstract class TicketState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->allowTransition([Open::class, Returned::class], InReview::class)
            ->allowTransition(InReview::class, Returned::class)
            ->allowTransition(InReview::class, Approved::class, Approve::class)
            ->allowTransition(InReview::class, Rejected::class);
    }
}
