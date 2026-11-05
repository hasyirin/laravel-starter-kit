<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\User;

use App\Domain\User\Models\User;
use App\Domain\User\Support\UserAuditLog;
use App\Livewire\Pages\Internal\Controls\AuditEntryModal as Modal;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

class AuditEntryModal extends Modal
{
    #[Locked]
    public User $user;

    protected function authorizeLog(): void
    {
        $this->authorize('view', [$this->user, 'audit-log']);
    }

    protected function find(int $id): Activity
    {
        return UserAuditLog::find($this->user, $id);
    }
}
