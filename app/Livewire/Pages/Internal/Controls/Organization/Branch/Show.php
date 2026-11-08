<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization\Branch;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Forms\Internal\BranchForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Show extends Page
{
    use CanBeDisabled;

    #[Locked]
    public Organization $organization;

    #[Locked]
    public Branch $branch;

    public BranchForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->branch);

        $this->form->set($this->organization, $this->branch);
    }

    public function isDisabled(?string $part = null): bool
    {
        return $this->disabled === true || $this->cannot('update', $this->branch);
    }

    public function save(): void
    {
        $this->authorize('update', $this->branch);
        $this->ensureEnabled();

        $this->branch = $this->form->save();
        $this->form->set($this->organization, $this->branch);

        $this->notifySaved((string) __('organization/branches.branch'), created: false);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.branch.show')->title($this->branch->name);
    }
}
