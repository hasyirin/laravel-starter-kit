<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\System;

use App\Domain\User\Models\Profile;
use App\Livewire\DataTables\AuditLogDataTable as DataTable;
use App\Livewire\Pages\System\AuditEntryModal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Livewire\Attributes\Defer;
use Spatie\Activitylog\Models\Activity;

#[Defer]
class AuditLogDataTable extends DataTable
{
    public function mount(): void
    {
        $this->authorize(config()->string('system.permissions.system.audit-log.view'));
    }

    /**
     * @return Builder<Activity>
     */
    protected function query(): Builder
    {
        return Activity::query()
            ->with(['causer' => fn (MorphTo $causer): MorphTo => $causer->withTrashed(), 'subject' => fn (MorphTo $subject): MorphTo => $subject->morphWith([Profile::class => ['user']])])
            ->latest('id');
    }

    protected function modal(): string
    {
        return AuditEntryModal::class;
    }

    protected function showsImpersonator(): bool
    {
        return true;
    }
}
