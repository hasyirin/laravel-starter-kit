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

    abstract public function canChange(string $type): bool;

    abstract protected function subject(): ?User;

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
        $readOnly = ! $this->canChange('roles');
        $locked = $this->lockedRoles();

        return $this->roles->map(fn (Role $role): Option => Option::make($role->name)
            ->label($role->label)
            ->disabled($readOnly || $locked->contains($role->name)));
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
        $isSelf = $this->isSelf();

        return $this->heldPermissions()
            ->filter(fn (string $name): bool => $isSelf && $name === $this->bypassPermission())
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

    protected function fillRolesAndPermissions(): void
    {
        $this->form->roles = $this->heldRoles()->all();
        $this->form->permissions = $this->visiblePermissions($this->heldPermissions())->all();
    }

    /**
     * @return array<int, string>|null
     */
    protected function rolesToSave(): ?array
    {
        if (! $this->canChange('roles')) {
            return null;
        }

        return $this->form->roles;
    }

    /**
     * @param  array<int, string>|null  $roles
     * @return array<int, string>|null
     */
    protected function permissionsToSave(?array $roles): ?array
    {
        if (! $this->canChange('permissions')) {
            return null;
        }

        $fromRoles = Role::query()
            ->with('permissions')
            ->whereIn('name', $roles ?? $this->heldRoles())
            ->get()
            ->flatMap(fn (Role $role): Collection => $role->permissions->pluck('name'));

        $held = $this->heldPermissions();

        return collect($this->form->permissions)
            ->filter(fn (string $name): bool => $held->contains($name) || $fromRoles->doesntContain($name))
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, string>
     */
    private function heldRoles(): Collection
    {
        return $this->names($this->subject()?->profile?->getRoleNames());
    }

    /**
     * @return Collection<int, string>
     */
    private function heldPermissions(): Collection
    {
        return $this->names($this->subject()?->profile?->getPermissionNames());
    }

    /**
     * @return Collection<int, string>
     */
    private function lockedRoles(): Collection
    {
        $isSelf = $this->isSelf();
        $held = $this->heldRoles();

        return $this->roles
            ->filter(fn (Role $role): bool => $isSelf && $role->checkPermissionTo($this->bypassPermission()) && $held->contains($role->name))
            ->map(fn (Role $role): string => $role->name)
            ->values();
    }

    private function isSelf(): bool
    {
        $subject = $this->subject();

        if ($subject === null) {
            return false;
        }

        return $subject->is($this->actor());
    }

    private function actor(): User
    {
        /** @var User $actor */
        $actor = auth()->user();

        return $actor;
    }

    private function actorHasBypass(): bool
    {
        return $this->actor()->checkPermissionTo($this->bypassPermission());
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

    private function bypassPermission(): string
    {
        return config()->string('system.permissions.bypass-authorization');
    }

    /**
     * @param  Collection<array-key, mixed>|null  $names
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
     * @return array<int, string>
     */
    private function namesOf(Builder $query): array
    {
        /** @var array<int, string> */
        return $query->orderBy('name')->pluck('name', 'id')->all();
    }
}
