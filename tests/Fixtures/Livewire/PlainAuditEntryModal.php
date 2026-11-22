<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Pages\Shared\AuditEntryModal;
use Spatie\Activitylog\Models\Activity;

class PlainAuditEntryModal extends AuditEntryModal
{
    protected function authorizeLog(): void {}

    protected function find(int $id): Activity
    {
        return Activity::query()->findOrFail($id);
    }
}
