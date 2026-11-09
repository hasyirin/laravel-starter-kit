<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization\Department;

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Forms\Internal\DepartmentForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Show extends Page
{
    use CanBeDisabled;

    #[Locked]
    public Organization $organization;

    #[Locked]
    public Department $department;

    public DepartmentForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->department);

        $this->form->set($this->organization, $this->department);
    }

    public function isDisabled(): bool
    {
        return $this->disabled === true || $this->cannot('update', $this->department);
    }

    public function save(): void
    {
        $this->authorize('update', $this->department);
        $this->ensureEnabled();

        $this->department = $this->form->save();
        $this->form->set($this->organization, $this->department);

        $this->notifySaved((string) __('organization/departments.department'), created: false);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.department.show')->title($this->department->name);
    }
}
