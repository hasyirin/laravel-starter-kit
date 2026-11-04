<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Admin\User;

use App\Domain\User\Models\User;
use App\Domain\User\Support\UserAuditLog;
use App\Livewire\DataTables\Admin\AuditLogDataTable as DataTable;
use App\Livewire\Pages\Admin\Controls\User\AuditEntryModal;
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
    public string $direction = 'by';

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
        return $this->direction === 'on';
    }
}
