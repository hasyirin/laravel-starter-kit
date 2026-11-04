<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Admin\User;

use App\Domain\User\Models\User;
use App\Domain\User\Support\UserActivities;
use App\Livewire\DataTables\Admin\ActivityDataTable as DataTable;
use App\Livewire\Pages\Admin\Controls\User\ActivityModal;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Models\Activity;

#[Defer]
class ActivityDataTable extends DataTable
{
    #[Locked]
    public int $userId;

    #[Locked]
    public string $direction = 'by';

    public function mount(): void
    {
        $this->authorize('view', [$this->user, 'activities']);
    }

    #[Computed]
    public function user(): User
    {
        return User::query()->findOrFail($this->userId);
    }

    /**
     * @return Builder<Activity>
     */
    protected function query(): Builder
    {
        return UserActivities::query($this->user, $this->direction);
    }

    protected function modal(): string
    {
        return ActivityModal::class;
    }

    protected function showsCauser(): bool
    {
        return $this->direction === 'on';
    }
}
