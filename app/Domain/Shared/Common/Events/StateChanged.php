<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class StateChanged extends \Spatie\ModelStates\Events\StateChanged implements ShouldDispatchAfterCommit {}
