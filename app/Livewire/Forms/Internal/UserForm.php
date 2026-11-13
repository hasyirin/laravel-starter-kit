<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Internal;

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Support\PermissionTree;
use App\Domain\User\Actions\Management\CreateUser;
use App\Domain\User\Actions\Management\UpdateUser;
use App\Domain\User\Concerns\UserInformationValidationRules;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Placement;
use App\Livewire\Form;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;

class UserForm extends Form
{
    use UserInformationValidationRules;

    #[Locked]
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?int $organization_id = null;

    public ?int $branch_id = null;

    public ?int $department_id = null;

    public ?int $position_id = null;

    /**
     * @var array<int, string>
     */
    public array $roles = [];

    /**
     * @var array<int, string>
     */
    public array $permissions = [];

    public function set(User $user): static
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->reset('password', 'password_confirmation');

        $profile = $user->profile;
        $this->organization_id = $profile?->organization_id;
        $this->branch_id = $profile?->branch_id;
        $this->department_id = $profile?->department_id;
        $this->position_id = $profile?->position_id;

        $bypassPermission = config()->string('system.permissions.bypass-authorization');
        $actorBypasses = $this->actor()->checkPermissionTo($bypassPermission);

        $this->roles = $this->names($profile?->getRoleNames())->all();
        $this->permissions = $this->names($profile?->getPermissionNames())
            ->reject(fn (string $name): bool => $name === $bypassPermission && ! $actorBypasses)
            ->values()
            ->all();

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->userInformationRules($this->user?->id, phoneRequired: ! $this->grantsBypass()),
            'password' => ['nullable', Rule::requiredIf(! $this->user instanceof User), 'string', Password::default(), 'confirmed'],
            'organization_id' => [
                'nullable',
                'integer',
                Rule::exists('organizations', 'id')
                    ->whereNull('deleted_at'),
            ],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')
                    ->whereNull('deleted_at')
                    ->where('organization_id', $this->organization_id),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')
                    ->whereNull('deleted_at')
                    ->where('organization_id', $this->organization_id),
            ],
            'position_id' => [
                'nullable',
                'integer',
                Rule::exists('positions', 'id')
                    ->whereNull('deleted_at')
                    ->where('organization_id', $this->organization_id)
                    ->where('department_id', $this->department_id),
            ],
            'roles' => ['array', 'list'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'permissions' => ['array', 'list'],
            'permissions.*' => ['string', Rule::in(PermissionTree::configured())],
        ];
    }

    public function placement(): Placement
    {
        return new Placement(
            organizationId: $this->organization_id,
            branchId: $this->branch_id,
            departmentId: $this->department_id,
            positionId: $this->position_id,
        );
    }

    public function editable(string $part): bool
    {
        if (! $this->user instanceof User) {
            return $this->can('create', [User::class, $part]);
        }

        return $this->can('update', [$this->user, $part]);
    }

    private function grantsBypass(): bool
    {
        $bypassPermission = config()->string('system.permissions.bypass-authorization');
        $profile = $this->user?->profile;
        $permissions = $this->editable('permissions') ? collect($this->permissions) : $this->names($profile?->getPermissionNames());

        if ($permissions->contains($bypassPermission)) {
            return true;
        }

        $roles = $this->editable('roles') ? collect($this->roles) : $this->names($profile?->getRoleNames());

        return Role::query()
            ->whereIn('name', $roles)
            ->whereRelation('permissions', 'name', $bypassPermission)
            ->exists();
    }

    private function actor(): User
    {
        /** @var User $actor */
        $actor = auth()->user();

        return $actor;
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
     * @throws AuthorizationException
     */
    public function save(): User
    {
        $this->email = str($this->email)->lower()->value();
        $this->validate();

        $roles = $this->editable('roles') ? $this->roles : null;
        $permissions = $this->editable('permissions') ? $this->permissions : null;
        $phone = $this->phone === '' ? null : $this->phone;

        if (! $this->user instanceof User) {
            return CreateUser::make()->execute(
                actor: $this->actor(),
                name: $this->name,
                email: $this->email,
                phone: $phone,
                password: $this->password,
                placement: $this->placement(),
                roles: $roles,
                permissions: $permissions,
            );
        }

        $password = $this->password === '' ? null : $this->password;

        UpdateUser::make()->execute(
            actor: $this->actor(),
            user: $this->user,
            name: $this->name,
            email: $this->email,
            phone: $phone,
            password: $password,
            placement: $this->placement(),
            roles: $roles,
            permissions: $permissions,
        );

        return $this->user;
    }
}
