<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Role;

use App\Domain\Shared\Permission\Models\Role;
use App\Livewire\Pages\Internal\Controls\AuditEntryModal as Modal;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

class AuditEntryModal extends Modal
{
    #[Locked]
    public Role $role;

    protected function authorizeLog(): void
    {
        $this->authorize('view', [$this->role, 'audit-log']);
    }

    protected function find(int $id): Activity
    {
        return Activity::query()->with('causer')->whereMorphedTo('subject', $this->role)->findOrFail($id);
    }
}
