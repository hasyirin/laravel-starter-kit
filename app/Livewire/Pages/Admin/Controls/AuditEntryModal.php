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

abstract class AuditEntryModal extends Modal
{
    #[Locked]
    public ?int $entryId = null;

    abstract protected function authorizeLog(): void;

    abstract protected function find(int $id): Activity;

    public function mount(): void
    {
        $this->authorizeLog();
    }

    #[On('show')]
    public function show(?int $id = null): void
    {
        $this->authorizeLog();

        $this->entryId = $id;
        unset($this->entry);

        abort_unless($this->entry instanceof Activity, 404);

        parent::show();
    }

    #[Computed]
    public function entry(): ?Activity
    {
        if ($this->entryId === null) {
            return null;
        }

        $entry = $this->find($this->entryId);

        if ($entry->subject instanceof Profile) {
            $entry->subject->loadMissing(['roles', 'permissions']);
        }

        return $entry;
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.audit-entry-modal');
    }
}
