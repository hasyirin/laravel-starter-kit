<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System;

use App\Livewire\Pages\Shared\AuditEntryModal as Modal;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity;

class AuditEntryModal extends Modal
{
    protected function authorizeLog(): void
    {
        $this->authorize(config()->string('system.permissions.system.logs.audits.view'));
    }

    protected function find(int $id): Activity
    {
        return Activity::query()->with(['causer' => fn (MorphTo $causer): MorphTo => $causer->withTrashed()])->findOrFail($id);
    }
}
