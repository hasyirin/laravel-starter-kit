<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Internal;

use App\Domain\Shared\Organization\Actions\SavePosition;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Livewire\Form;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

class PositionForm extends Form
{
    #[Locked]
    public Organization $organization;

    #[Locked]
    public Position $position;

    public ?int $department_id = null;

    public string $name = '';

    public function set(Organization $organization, Position $position, ?Department $department = null): static
    {
        $this->organization = $organization;
        $this->position = $position;
        $this->department_id = $position->exists ? $position->department_id : $department?->id;
        $this->name = $position->name ?? '';

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')
                    ->where('organization_id', $this->organization->id)
                    ->withoutTrashed(),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')
                    ->where('organization_id', $this->organization->id)
                    ->where(fn (Builder $query): Builder => $this->department_id === null
                        ? $query->whereNull('department_id')
                        : $query->where('department_id', $this->department_id))
                    ->withoutTrashed()
                    ->ignore($this->position->id),
            ],
        ];
    }

    public function save(): Position
    {
        $this->validate();

        $department = $this->department_id === null ? null : Department::query()->findOrFail($this->department_id);

        return SavePosition::make()->execute($this->organization, $this->position, $department, $this->name);
    }
}
