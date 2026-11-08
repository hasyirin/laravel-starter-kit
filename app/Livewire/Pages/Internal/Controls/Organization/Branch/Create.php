<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization\Branch;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Forms\Internal\BranchForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Create extends Page
{
    #[Locked]
    public Organization $organization;

    public BranchForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->organization);
        $this->authorize('create', Branch::class);

        $this->form->set($this->organization);
    }

    public function save(): void
    {
        $this->authorize('create', Branch::class);

        $branch = $this->form->save();

        $this->notifySaved((string) __('organization/branches.branch'), created: true);
        $this->redirect(route('internal.controls.organizations.branches.show', [$this->organization, $branch]), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.branch.create')->title(__('organization/branches.create.title'));
    }
}
