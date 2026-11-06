<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Shared;

use App\Livewire\Modal;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Spatie\Activitylog\Models\Activity;

abstract class AuditEntryModal extends Modal
{
    #[Locked]
    public ?Activity $entry = null;

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

        abort_if($id === null, 404);

        $this->entry = $this->find($id);

        parent::show();
    }

    public function render(): View
    {
        return view('livewire.pages.shared.audit-entry-modal');
    }
}
