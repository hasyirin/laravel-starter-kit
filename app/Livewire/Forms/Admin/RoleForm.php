<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Admin;

use App\Domain\Shared\Permission\Actions\CreateRole;
use App\Domain\Shared\Permission\Actions\UpdateRole;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Support\PermissionTree;
use App\Domain\User\Models\User;
use App\Livewire\Form;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

class RoleForm extends Form
{
    #[Locked]
    public ?Role $role = null;

    public string $name = '';

    /**
     * @var array<int, string>
     */
    public array $permissions = [];

    public function set(Role $role): static
    {
        $this->role = $role;
        $this->name = $role->name;
        $this->permissions = $role->getPermissionNames()
            ->map(fn (mixed $name): string => (string) $name)
            ->reject(fn (string $name): bool => $name === $this->bypassPermission() && ! $this->actor()->checkPermissionTo($this->bypassPermission()))
            ->values()
            ->all();

        return $this;
    }

    /**
     * @throws AuthorizationException
     */
    public function save(): Role
    {
        $this->validate();

        $permissions = $this->editable('permissions') ? $this->permissions : null;

        if (! $this->role instanceof Role) {
            return CreateRole::make()->execute($this->actor(), $this->name, $permissions);
        }

        return UpdateRole::make()->execute($this->actor(), $this->role, $this->editable('name') ? $this->name : null, $permissions);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($this->role?->id)],
            'permissions' => ['array', 'list'],
            'permissions.*' => ['string', Rule::in($this->assignablePermissions())],
        ];
    }

    public function editable(string $part): bool
    {
        if (! $this->role instanceof Role) {
            return $this->can('create', [Role::class, $part]);
        }

        return ($part !== 'name' || ! $this->role->is_system) && $this->can('update', [$this->role, $part]);
    }

    /**
     * @return Collection<int, string>
     */
    private function assignablePermissions(): Collection
    {
        if ($this->actor()->checkPermissionTo($this->bypassPermission())) {
            return PermissionTree::configured();
        }

        return PermissionTree::configured()
            ->reject(fn (string $name): bool => $name === $this->bypassPermission())
            ->values();
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
}
