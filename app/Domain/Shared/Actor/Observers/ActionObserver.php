<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actor\Observers;

use App\Domain\Shared\Actor\Models\Action;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;

class ActionObserver
{
    public function saving(Action $action): void
    {
        $action->profile()->associate($action->actor_type === new User()->getMorphClass()
            ? Profile::query()->where('user_id', $action->actor_id)->latest('id')->first()
            : null);
    }
}
