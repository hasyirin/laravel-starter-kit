<?php

declare(strict_types=1);

namespace Tests\Fixtures\Transitions;

use App\Domain\Shared\Common\Transitions\Transition;
use Carbon\CarbonImmutable;
use Spatie\ModelStates\State;
use Tests\Fixtures\Models\Ticket;

/**
 * @extends Transition<Ticket>
 */
class Approve extends Transition
{
    /**
     * @param  State<Ticket>  $newState
     */
    public function __construct(Ticket $model, string $field, State $newState, private readonly ?CarbonImmutable $approvedAt = null)
    {
        parent::__construct($model, $field, $newState);
    }

    public function reason(): ?string
    {
        return $this->model->approvable ? null : 'The ticket is not ready for approval.';
    }

    protected function apply(): void
    {
        $this->model->approved_at = $this->approvedAt ?? now();
    }
}
