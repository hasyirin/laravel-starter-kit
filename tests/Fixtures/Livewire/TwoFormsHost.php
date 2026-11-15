<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Component;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;

class TwoFormsHost extends Component
{
    #[Locked]
    public Organization $organization;

    #[Locked]
    public ?Department $department = null;

    public OrganizationDepartmentForm $form;

    public InnerValidationForm $innerForm;

    public function mount(): void
    {
        $this->form->set($this->organization);
    }

    public function save(): void
    {
        $department = DB::transaction(function (): Department {
            $department = $this->form->save();
            $this->innerForm->save();

            return $department;
        });

        $this->department = $department;
    }

    public function render(): string
    {
        return '<div>{{ $form->organization->name }}</div>';
    }
}
