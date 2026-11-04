<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Admin\Role;

use App\Domain\Shared\Permission\Models\Role;
use App\Livewire\DataTables\Admin\ActivityDataTable as DataTable;
use App\Livewire\Pages\Admin\Controls\Role\ActivityModal;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

#[Defer]
class ActivityDataTable extends DataTable
{
    #[Locked]
    public int $roleId;

    public function mount(): void
    {
        $this->authorize('view', [$this->role, 'activities']);
    }

    #[Computed]
    public function role(): Role
    {
        return Role::query()->findOrFail($this->roleId);
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
        return ActivityModal::class;
    }

    protected function showsSubject(): bool
    {
        return false;
    }
}
