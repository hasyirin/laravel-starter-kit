<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User;

use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Support\UserActivities;
use App\Livewire\Modal;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Spatie\Activitylog\Models\Activity;

class ActivityModal extends Modal
{
    #[Locked]
    public int $userId;

    #[Locked]
    public ?int $activityId = null;

    public function mount(): void
    {
        $this->authorize('view', [$this->user, 'activities']);
    }

    #[On('show')]
    public function show(?int $id = null): void
    {
        $this->authorize('view', [$this->user, 'activities']);

        $this->activityId = UserActivities::find($this->user, (int) $id)->id;
        unset($this->activity);

        parent::show();
    }

    #[Computed]
    public function user(): User
    {
        return User::query()->findOrFail($this->userId);
    }

    #[Computed]
    public function activity(): ?Activity
    {
        if ($this->activityId === null) {
            return null;
        }

        $activity = UserActivities::find($this->user, $this->activityId);

        if ($activity->subject instanceof Profile) {
            $activity->subject->loadMissing(['roles', 'permissions']);
        }

        return $activity;
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.user.activity-modal');
    }
}
