<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\Role;

use App\Domain\Shared\Permission\Models\Role;
use App\Livewire\Pages\Admin\Controls\ActivityModal as Modal;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

class ActivityModal extends Modal
{
    #[Locked]
    public int $roleId;

    #[Computed]
    public function role(): Role
    {
        return Role::query()->findOrFail($this->roleId);
    }

    protected function authorizeActivities(): void
    {
        $this->authorize('view', [$this->role, 'activities']);
    }

    protected function find(int $id): Activity
    {
        return Activity::query()->with('causer')->whereMorphedTo('subject', $this->role)->findOrFail($id);
    }
}
