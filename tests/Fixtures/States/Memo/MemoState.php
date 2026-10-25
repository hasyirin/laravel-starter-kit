<?php

declare(strict_types=1);

namespace Tests\Fixtures\States\Memo;

use App\Domain\Shared\Common\States\State;
use Spatie\ModelStates\Attributes\DefaultState;
use Spatie\ModelStates\StateConfig;
use Tests\Fixtures\Models\Memo;

/**
 * @extends State<Memo>
 */
#[DefaultState(Draft::class)]
abstract class MemoState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->allowTransition(Draft::class, Review::class)
            ->allowTransition(Review::class, Approved::class)
            ->allowTransition([Draft::class, Review::class, Approved::class, OnHold::class], OnHold::class)
            ->allowTransition(OnHold::class, Draft::class);
    }
}
