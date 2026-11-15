<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Domain\Shared\Organization\Actions\SaveDepartment;
use App\Domain\Shared\Organization\Actions\SaveOrganization;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Form;
use Livewire\Attributes\Locked;

class OrganizationDepartmentForm extends Form
{
    #[Locked]
    public Organization $organization;

    public string $name = '';

    public string $department_code = '';

    public string $department_name = '';

    public function set(Organization $organization): static
    {
        $this->organization = $organization;
        $this->name = $organization->name;

        return $this;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'department_code' => ['required', 'string'],
            'department_name' => ['required', 'string'],
        ];
    }

    protected function errorKey(string $key): string
    {
        return "department_$key";
    }

    public function save(): Department
    {
        $this->validate();

        return $this->transaction(function (): Department {
            SaveOrganization::make()->execute($this->organization, $this->organization->code, $this->name);

            return SaveDepartment::make()->execute($this->organization, new Department, $this->department_code, $this->department_name);
        });
    }
}
