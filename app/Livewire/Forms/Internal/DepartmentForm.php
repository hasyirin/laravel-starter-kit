<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Internal;

use App\Domain\Shared\Organization\Actions\SaveDepartment;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Form;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

class DepartmentForm extends Form
{
    #[Locked]
    public Organization $organization;

    #[Locked]
    public ?Department $department = null;

    public string $code = '';

    public string $name = '';

    public function set(Organization $organization, ?Department $department = null): static
    {
        $this->organization = $organization;
        $this->department = $department;
        $this->code = $department->code ?? '';
        $this->name = $department->name ?? '';

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('departments', 'code')
                    ->where('organization_id', $this->organization->id)
                    ->withoutTrashed()
                    ->ignore($this->department?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function save(): Department
    {
        $this->validate();

        return SaveDepartment::make()->execute($this->organization, $this->department ?? new Department, $this->code, $this->name);
    }
}
