<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Pages\Shared\AuditEntryModal;
use Spatie\Activitylog\Models\Activity;

class DescribedAuditEntryModal extends AuditEntryModal
{
    protected function authorizeLog(): void {}

    protected function find(int $id): Activity
    {
        return Activity::query()->findOrFail($id);
    }

    protected function descriptionWithoutChanges(Activity $entry): ?string
    {
        return $entry->getProperty('count') === 3 ? 'Sent <b>3</b> reminders.' : null;
    }
}
