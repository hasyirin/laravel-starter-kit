<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Admin\Role;

use App\Domain\Shared\Permission\Models\Role;
use App\Livewire\DataTables\Admin\AuditLogDataTable as DataTable;
use App\Livewire\Pages\Admin\Controls\Role\AuditEntryModal;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

#[Defer]
class AuditLogDataTable extends DataTable
{
    #[Locked]
    public Role $role;

    public function mount(): void
    {
        $this->authorize('view', [$this->role, 'audit-log']);
    }

    /**
     * @return Builder<Activity>
     */
    protected function query(): Builder
    {
        return Activity::query()->with('causer')->whereMorphedTo('subject', $this->role)->latest('id');
    }

    protected function modal(): string
    {
        return AuditEntryModal::class;
    }

    protected function showsSubject(): bool
    {
        return false;
    }
}
