<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User\Concerns;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Livewire\Forms\Admin\UserForm;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

/**
 * @property UserForm $form
 */
trait InteractsWithUserForm
{
    public UserForm $form;

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function organizations(): array
    {
        return $this->namesOf(Organization::query());
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function branches(): array
    {
        return $this->namesOf(Branch::query()->where('organization_id', $this->form->organization_id));
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function departments(): array
    {
        return $this->namesOf(Department::query()->where('organization_id', $this->form->organization_id));
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function positions(): array
    {
        return $this->namesOf(Position::query()
            ->where('organization_id', $this->form->organization_id)
            ->where('department_id', $this->form->department_id));
    }

    public function updatedFormOrganizationId(): void
    {
        $this->form->branch_id = null;
        $this->form->department_id = null;
        $this->form->position_id = null;
    }

    public function updatedFormDepartmentId(): void
    {
        $this->form->position_id = null;
    }

    /**
     * @template TModel of Branch|Department|Organization|Position
     *
     * @param  Builder<TModel>  $query
     * @return array<int, string>
     */
    private function namesOf(Builder $query): array
    {
        /** @var array<int, string> */
        return $query->orderBy('name')->pluck('name', 'id')->all();
    }
}
