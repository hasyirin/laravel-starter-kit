<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\Geography;

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Support\GeographyAuditLog;
use App\Livewire\DataTables\AuditLogDataTable as DataTable;
use App\Livewire\Pages\Internal\Controls\Geography\AuditEntryModal;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

#[Defer]
class AuditLogDataTable extends DataTable
{
    #[Locked]
    public Country|State|District|PostOffice $record;

    public function mount(): void
    {
        $this->authorize('view', [$this->record, 'audit-log']);
    }

    /**
     * @return Builder<Activity>
     */
    protected function query(): Builder
    {
        return GeographyAuditLog::query($this->record);
    }

    protected function modal(): string
    {
        return AuditEntryModal::class;
    }
}
