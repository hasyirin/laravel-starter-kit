<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Role\Concerns;

use App\Domain\Shared\Permission\Support\PermissionTree;
use App\Domain\User\Models\User;
use App\Livewire\Forms\Internal\RoleForm;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * @phpstan-import-type PermissionNode from PermissionTree
 *
 * @property RoleForm $form
 */
trait InteractsWithRoleForm
{
    public RoleForm $form;

    public function editable(string $part): bool
    {
        return $this->form->editable($part);
    }

    /**
     * @return PermissionNode
     */
    #[Computed]
    public function permissionTree(): array
    {
        $names = PermissionTree::configured();

        if (! $this->actorBypasses()) {
            $names = $names->reject(fn (string $name): bool => $name === $this->bypassPermission())->values();
        }

        return PermissionTree::build($names);
    }

    /**
     * @return array<string, array{label: string, permissions: array<int, string>}>
     */
    #[Computed]
    public function rolePermissions(): array
    {
        return [];
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function lockedPermissions(): Collection
    {
        $role = $this->form->role;
        $locked = $role !== null && $this->actor()->hasRole($role) && $role->checkPermissionTo($this->bypassPermission());

        return collect($locked ? [$this->bypassPermission()] : []);
    }

    private function actorBypasses(): bool
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
}
