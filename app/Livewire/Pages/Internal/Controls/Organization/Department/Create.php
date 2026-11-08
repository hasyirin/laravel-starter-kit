<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization\Department;

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Forms\Internal\DepartmentForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Create extends Page
{
    #[Locked]
    public Organization $organization;

    public DepartmentForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->organization);
        $this->authorize('create', Department::class);

        $this->form->set($this->organization);
    }

    public function save(): void
    {
        $this->authorize('create', Department::class);

        $department = $this->form->save();

        $this->notifySaved((string) __('organization/departments.department'), created: true);
        $this->redirect(route('internal.controls.organizations.departments.show', [$this->organization, $department]), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.department.create')->title(__('organization/departments.create.title'));
    }
}
