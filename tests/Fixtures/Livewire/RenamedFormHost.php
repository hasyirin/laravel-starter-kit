<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Component;
use Livewire\Attributes\Locked;

class RenamedFormHost extends Component
{
    #[Locked]
    public Organization $organization;

    public OrganizationDepartmentForm $departmentForm;

    public function mount(): void
    {
        $this->departmentForm->set($this->organization);
    }

    public function save(): void
    {
        $this->departmentForm->save();
    }

    public function render(): string
    {
        return '<div>@error(\'departmentForm.department_code\'){{ $message }}@enderror</div>';
    }
}
