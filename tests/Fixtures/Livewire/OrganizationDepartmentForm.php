<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Domain\Shared\Common\Support\ModelSnapshot;
use App\Domain\Shared\Organization\Actions\SaveDepartment;
use App\Domain\Shared\Organization\Actions\SaveOrganization;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

    public function save(): Department
    {
        $this->validate();

        try {
            return DB::transaction(function (): Department {
                ModelSnapshot::restoreOnRollBack($this->organization);

                SaveOrganization::make()->execute($this->organization, $this->organization->code, $this->name);

                return SaveDepartment::make()->execute($this->organization, new Department, $this->department_code, $this->department_name);
            });
        } catch (ValidationException $validationException) {
            $messages = collect($validationException->errors())->mapWithKeys(fn (mixed $errors, int|string $key): array => ["form.department_$key" => $errors]);

            throw ValidationException::withMessages($messages->all());
        }
    }
}
