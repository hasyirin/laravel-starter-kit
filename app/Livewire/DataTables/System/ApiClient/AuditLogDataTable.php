<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\System\ApiClient;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Support\ApiClientAuditLog;
use App\Livewire\DataTables\AuditLogDataTable as DataTable;
use App\Livewire\Pages\System\ApiClient\AuditEntryModal;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

#[Defer]
class AuditLogDataTable extends DataTable
{
    #[Locked]
    public ApiClient $apiClient;

    public function mount(): void
    {
        $this->authorize('view', [$this->apiClient, 'audit-log']);
    }

    /**
     * @return Builder<Activity>
     */
    protected function query(): Builder
    {
        return ApiClientAuditLog::query($this->apiClient);
    }

    protected function modal(): string
    {
        return AuditEntryModal::class;
    }
}
