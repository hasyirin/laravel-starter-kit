<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Support\OrganizationAuditLog;
use App\Livewire\Pages\Shared\AuditEntryModal as Modal;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

class AuditEntryModal extends Modal
{
    #[Locked]
    public Organization|Department|Branch $record;

    protected function authorizeLog(): void
    {
        $this->authorize('view', [$this->record, 'audit-log']);
    }

    protected function find(int $id): Activity
    {
        return OrganizationAuditLog::find($this->record, $id);
    }
}
