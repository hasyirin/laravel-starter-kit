<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Common\Models\Model;
use Carbon\CarbonImmutable;
use Spatie\ModelStates\HasStates;
use Tests\Fixtures\States\Ticket\TicketState;

/**
 * @property int $id
 * @property string $title
 * @property bool $approvable
 * @property CarbonImmutable|null $approved_at
 * @property TicketState $state
 */
class Ticket extends Model
{
    use HasStates;

    protected $fillable = [
        'title',
        'approvable',
    ];

    protected function casts(): array
    {
        return [
            'approvable' => 'boolean',
            'approved_at' => 'datetime',
            'state' => TicketState::class,
        ];
    }
}
