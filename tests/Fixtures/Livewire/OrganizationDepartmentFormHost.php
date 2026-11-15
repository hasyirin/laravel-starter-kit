<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Component;
use Livewire\Attributes\Locked;

class OrganizationDepartmentFormHost extends Component
{
    #[Locked]
    public Organization $organization;

    public OrganizationDepartmentForm $form;

    public function mount(): void
    {
        $this->form->set($this->organization);
    }

    public function save(): void
    {
        $this->form->save();
        $this->form->set($this->organization);
    }

    public function render(): string
    {
        return '<div>{{ $form->organization->name }}</div>';
    }
}
