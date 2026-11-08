<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\Organization;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Support\OrganizationAuditLog;
use App\Livewire\DataTables\AuditLogDataTable as DataTable;
use App\Livewire\Pages\Internal\Controls\Organization\AuditEntryModal;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

#[Defer]
class AuditLogDataTable extends DataTable
{
    #[Locked]
    public Organization|Department|Branch $record;

    public function mount(): void
    {
        $this->authorize('view', [$this->record, 'audit-log']);
    }

    /**
     * @return Builder<Activity>
     */
    protected function query(): Builder
    {
        return OrganizationAuditLog::query($this->record);
    }

    protected function modal(): string
    {
        return AuditEntryModal::class;
    }
}
