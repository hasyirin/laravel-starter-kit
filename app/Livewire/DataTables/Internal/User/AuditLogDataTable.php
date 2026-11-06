<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\User;

use App\Domain\User\Enums\AuditDirection;
use App\Domain\User\Models\User;
use App\Domain\User\Support\UserAuditLog;
use App\Livewire\DataTables\Internal\AuditLogDataTable as DataTable;
use App\Livewire\Pages\Internal\Controls\User\AuditEntryModal;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

#[Defer]
class AuditLogDataTable extends DataTable
{
    #[Locked]
    public User $user;

    #[Locked]
    public AuditDirection $direction = AuditDirection::By;

    public function mount(): void
    {
        $this->authorize('view', [$this->user, 'audit-log']);
    }

    /**
     * @return Builder<Activity>
     */
    protected function query(): Builder
    {
        return UserAuditLog::query($this->user, $this->direction);
    }

    protected function modal(): string
    {
        return AuditEntryModal::class;
    }

    protected function showsCauser(): bool
    {
        return $this->direction === AuditDirection::On;
    }
}
