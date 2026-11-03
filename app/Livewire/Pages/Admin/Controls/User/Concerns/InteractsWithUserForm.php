<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User\Concerns;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Support\PermissionTree;
use App\Domain\User\Models\User;
use App\Livewire\Components\Option;
use App\Livewire\Forms\Admin\UserForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * @phpstan-import-type PermissionNode from PermissionTree
 *
 * @property UserForm $form
 */
trait InteractsWithUserForm
{
    public UserForm $form;

    public function editable(string $part): bool
    {
        return $this->form->editable($part);
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function organizations(): Collection
    {
        return $this->namesOf(Organization::query());
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function branches(): Collection
    {
        return $this->namesOf(Branch::query()->where('organization_id', $this->form->organization_id));
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function departments(): Collection
    {
        return $this->namesOf(Department::query()->where('organization_id', $this->form->organization_id));
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function positions(): Collection
    {
        return $this->namesOf(Position::query()
            ->where('organization_id', $this->form->organization_id)
            ->where('department_id', $this->form->department_id));
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        $roles = Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->get();

        if ($this->actorHasBypass()) {
            return $roles;
        }

        $held = $this->heldRoles();

        return $roles
            ->reject(fn (Role $role): bool => $role->checkPermissionTo($this->bypassPermission()) && $held->doesntContain($role->name))
            ->values();
    }

    /**
     * @return Collection<int, Option>
     */
    #[Computed]
    public function roleOptions(): Collection
    {
        $readOnly = ! $this->editable('roles');
        $self = $this->editingSelf();
        $held = $this->heldRoles();

        return $this->roles->map(fn (Role $role): Option => Option::make($role->name)
            ->label($role->label)
            ->disabled($readOnly || ($self && $held->contains($role->name) && $role->checkPermissionTo($this->bypassPermission()))));
    }

    /**
     * @return PermissionNode
     */
    #[Computed]
    public function permissionTree(): array
    {
        return PermissionTree::build($this->visiblePermissions(PermissionTree::configured()));
    }

    /**
     * @return array<string, array{label: string, permissions: array<int, string>}>
     */
    #[Computed]
    public function rolePermissions(): array
    {
        return $this->roles
            ->mapWithKeys(fn (Role $role): array => [
                $role->name => [
                    'label' => $role->label,
                    'permissions' => $this->visiblePermissions($this->names($role->permissions->pluck('name')))->all(),
                ],
            ])
            ->all();
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function lockedPermissions(): Collection
    {
        $self = $this->editingSelf();

        return $this->heldPermissions()
            ->filter(fn (string $name): bool => $self && $name === $this->bypassPermission())
            ->values();
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
     * @return Collection<int, string>
     */
    private function heldRoles(): Collection
    {
        return $this->names($this->form->user?->profile?->getRoleNames());
    }

    /**
     * @return Collection<int, string>
     */
    private function heldPermissions(): Collection
    {
        return $this->names($this->form->user?->profile?->getPermissionNames());
    }

    /**
     * @param  Collection<int, string>  $names
     * @return Collection<int, string>
     */
    private function visiblePermissions(Collection $names): Collection
    {
        if ($this->actorHasBypass()) {
            return $names;
        }

        return $names
            ->reject(fn (string $name): bool => $name === $this->bypassPermission())
            ->values();
    }

    private function editingSelf(): bool
    {
        return $this->form->user?->is($this->actor()) ?? false;
    }

    private function actorHasBypass(): bool
    {
        return $this->actor()->checkPermissionTo($this->bypassPermission());
    }

    private function actor(): User
    {
        /** @var User $actor */
        $actor = auth()->user();

        return $actor;
    }

    private function bypassPermission(): string
    {
        return config()->string('system.permissions.bypass-authorization');
    }

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param  Collection<TKey, TValue>|null  $names
     * @return Collection<int, string>
     */
    private function names(?Collection $names): Collection
    {
        return collect($names)
            ->map(fn (mixed $name): string => (string) $name)
            ->values();
    }

    /**
     * @template TModel of Branch|Department|Organization|Position
     *
     * @param  Builder<TModel>  $query
     * @return Collection<int, string>
     */
    private function namesOf(Builder $query): Collection
    {
        /** @var Collection<int, string> */
        return $query->orderBy('name')->pluck('name', 'id');
    }
}
