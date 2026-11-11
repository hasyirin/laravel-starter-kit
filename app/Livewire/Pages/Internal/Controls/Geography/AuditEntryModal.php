<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography;

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Support\GeographyAuditLog;
use App\Livewire\Pages\Shared\AuditEntryModal as Modal;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

class AuditEntryModal extends Modal
{
    #[Locked]
    public Country|State|District|PostOffice $record;

    protected function authorizeLog(): void
    {
        $this->authorize('view', [$this->record, 'audit-log']);
    }

    protected function find(int $id): Activity
    {
        return GeographyAuditLog::find($this->record, $id);
    }
}
