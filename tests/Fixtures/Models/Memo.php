<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Common\Models\Model;
use Spatie\ModelStates\HasStates;
use Tests\Fixtures\States\Memo\MemoState;

/**
 * @property int $id
 * @property MemoState $state
 */
class Memo extends Model
{
    use HasStates;

    protected function casts(): array
    {
        return [
            'state' => MemoState::class,
        ];
    }
}
