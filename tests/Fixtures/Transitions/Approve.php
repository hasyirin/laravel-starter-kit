<?php

declare(strict_types=1);

namespace Tests\Fixtures\Transitions;

use App\Domain\Shared\Common\Transitions\Transition;
use Tests\Fixtures\Models\Ticket;

/**
 * @extends Transition<Ticket>
 */
class Approve extends Transition
{
    public function reason(): ?string
    {
        return $this->model->approvable ? null : 'The ticket is not ready for approval.';
    }

    protected function apply(): void
    {
        $this->model->approved_at = now();
    }
}
