<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User;

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Support\PermissionTree;
use App\Domain\User\Actions\ChangeUserProfile;
use App\Domain\User\Models\User;
use App\Livewire\Component;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * @phpstan-import-type PermissionNode from PermissionTree
 */
class Permissions extends Component
{
    #[Locked]
    public int $userId;

    public function mount(): void
    {
        $this->authorize('view', $this->user);
    }

    #[Computed]
    public function user(): User
    {
        return User::query()->findOrFail($this->userId);
    }

    /**
     * @return PermissionNode
     */
    #[Computed]
    public function tree(): array
    {
        return PermissionTree::build(array_values(array_filter($this->configPermissions(), $this->isVisible(...))));
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function direct(): array
    {
        $names = $this->user->profile?->getPermissionNames()->map(fn (mixed $name): string => (string) $name)->all() ?? [];

        return array_values(array_filter($names, $this->isVisible(...)));
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function fromRoles(): array
    {
        $fromRoles = [];

        /** @var Role $role */
        foreach ($this->user->profile?->loadMissing('roles.permissions')->roles ?? [] as $role) {
            foreach ($role->permissions as $permission) {
                $fromRoles[(string) $permission->name] ??= $role->label;
            }
        }

        return array_filter($fromRoles, $this->isVisible(...), ARRAY_FILTER_USE_KEY);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function locked(): array
    {
        return $this->actor()->is($this->user) ? array_values(array_intersect($this->direct, [$this->bypassPermission()])) : [];
    }

    #[Computed]
    public function readOnly(): bool
    {
        return $this->cannot('update', [$this->user, 'permissions']);
    }

    /**
     * @param  array<array-key, mixed>  $permissions
     * @return list<string>
     */
    public function save(array $permissions, ChangeUserProfile $changeUserProfile): array
    {
        $this->authorize('update', [$this->user, 'permissions']);

        validator(['permissions' => $permissions], [
            'permissions' => ['array', 'list'],
            'permissions.*' => ['string', Rule::in($this->configPermissions())],
        ])->validate();

        /** @var list<string> $permissions */
        $direct = array_values(array_filter(
            $permissions,
            fn (string $name): bool => in_array($name, $this->direct, true) || ! array_key_exists($name, $this->fromRoles),
        ));

        $changeUserProfile->execute($this->actor(), $this->user, permissions: $direct);

        unset($this->user, $this->direct);

        Flux::toast(variant: 'success', text: __('user/users.permissions.saved'));

        return $this->direct;
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.user.permissions');
    }

    /**
     * @return list<string>
     */
    private function configPermissions(): array
    {
        return array_values(collect(config('system.permissions'))->flatten()->map(fn (mixed $name): string => (string) $name)->all());
    }

    private function isVisible(string $name): bool
    {
        return $name !== $this->bypassPermission() || $this->actorHasBypass();
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

    private function bypassPermission(): string
    {
        return config()->string('system.permissions.bypass-authorization');
    }
}
