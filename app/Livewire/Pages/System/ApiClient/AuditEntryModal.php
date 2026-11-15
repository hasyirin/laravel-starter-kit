<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System\ApiClient;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Support\ApiClientAuditLog;
use App\Livewire\Pages\Shared\AuditEntryModal as Modal;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

class AuditEntryModal extends Modal
{
    #[Locked]
    public ApiClient $apiClient;

    protected function authorizeLog(): void
    {
        $this->authorize('view', [$this->apiClient, 'audit-log']);
    }

    protected function find(int $id): Activity
    {
        return ApiClientAuditLog::find($this->apiClient, $id);
    }
}
