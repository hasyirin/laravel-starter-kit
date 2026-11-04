<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User;

use App\Domain\User\Models\User;
use App\Domain\User\Support\UserActivities;
use App\Livewire\Pages\Admin\Controls\ActivityModal as Modal;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

class ActivityModal extends Modal
{
    #[Locked]
    public User $user;

    protected function authorizeActivities(): void
    {
        $this->authorize('view', [$this->user, 'activities']);
    }

    protected function find(int $id): Activity
    {
        return UserActivities::find($this->user, $id);
    }
}
