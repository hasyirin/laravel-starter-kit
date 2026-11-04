<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls;

use App\Domain\User\Models\Profile;
use App\Livewire\Modal;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Spatie\Activitylog\Models\Activity;

abstract class ActivityModal extends Modal
{
    #[Locked]
    public ?int $activityId = null;

    abstract protected function authorizeActivities(): void;

    abstract protected function find(int $id): Activity;

    public function mount(): void
    {
        $this->authorizeActivities();
    }

    #[On('show')]
    public function show(?int $id = null): void
    {
        $this->authorizeActivities();

        $this->activityId = $id;
        unset($this->activity);

        abort_unless($this->activity instanceof Activity, 404);

        parent::show();
    }

    #[Computed]
    public function activity(): ?Activity
    {
        if ($this->activityId === null) {
            return null;
        }

        $activity = $this->find($this->activityId);

        if ($activity->subject instanceof Profile) {
            $activity->subject->loadMissing(['roles', 'permissions']);
        }

        return $activity;
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.activity-modal');
    }
}
