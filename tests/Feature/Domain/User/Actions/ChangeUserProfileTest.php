<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Actions\ChangeUserProfile;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    Role::query()->create(['name' => 'editor']);
    Role::query()->create(['name' => 'viewer']);

    $this->actor = User::factory()->create();
    $this->user = User::factory()->create();
});

/**
 * @return array<string, int>
 */
function changeUserProfilePlacement(Department $department): array
{
    return [
        'organization_id' => $department->organization_id,
        'branch_id' => Branch::factory()->for($department->organization)->create()->id,
        'department_id' => $department->id,
        'position_id' => Position::factory()->forDepartment($department)->create()->id,
    ];
}

/**
 * @return array{placement: array<string, mixed>, roles: list<string>, permissions: list<string>}
 */
function changeUserProfileSnapshot(?Profile $profile): array
{
    expect($profile)->not->toBeNull();

    $profile = Profile::query()->findOrFail($profile->id);

    return [
        'placement' => $profile->only(['organization_id', 'branch_id', 'department_id', 'position_id']),
        'roles' => $profile->roles->pluck('name')->sort()->values()->all(),
        'permissions' => $profile->permissions->pluck('name')->sort()->values()->all(),
    ];
}

test('a new profile copies the placement, roles, and permissions it was not given', function (string $given): void {
    $placement = changeUserProfilePlacement(Department::factory()->create());
    Profile::factory()->for($this->user)->state($placement)->withRoles('editor')->withPermissions('admin.access')->create();

    $newPlacement = changeUserProfilePlacement(Department::factory()->create());
    $arguments = [
        'placement' => ['placement' => $newPlacement],
        'roles' => ['roles' => ['viewer']],
        'permissions' => ['permissions' => ['admin.controls.users.view']],
    ][$given];

    $profile = ChangeUserProfile::make()->execute($this->actor, $this->user, ...$arguments);

    expect(changeUserProfileSnapshot($profile))->toBe([
        'placement' => $given === 'placement' ? $newPlacement : $placement,
        'roles' => $given === 'roles' ? ['viewer'] : ['editor'],
        'permissions' => $given === 'permissions' ? ['admin.controls.users.view'] : ['admin.access'],
    ])->and($this->user->profiles()->count())->toBe(2);
})->with(['placement', 'roles', 'permissions']);

test('nothing changes when the sets are the same', function (): void {
    $placement = changeUserProfilePlacement(Department::factory()->create());
    Profile::factory()->for($this->user)->state($placement)->withRoles('editor', 'viewer')->withPermissions('admin.access')->create();

    $profile = ChangeUserProfile::make()->execute($this->actor, $this->user, $placement, ['viewer', 'editor'], ['admin.access']);

    expect($profile)->toBeNull()
        ->and($this->user->profiles()->count())->toBe(1);
});

test('a change builds on the profile that is current when it saves', function (): void {
    Profile::factory()->for($this->user)->withPermissions('admin.access')->create();
    $user = User::query()->findOrFail($this->user->id);
    Profile::factory()->for($this->user)->withPermissions('admin.controls.users.view')->create();

    $profile = ChangeUserProfile::make()->execute($this->actor, $user, roles: ['editor']);

    expect(changeUserProfileSnapshot($profile)['permissions'])->toBe(['admin.controls.users.view'])
        ->and(changeUserProfileSnapshot($profile)['roles'])->toBe(['editor']);
});

test('the bypass guard reads the profile that is current when it saves', function (): void {
    Profile::factory()->for($this->user)->create();
    $user = User::query()->findOrFail($this->user->id);
    $user->load('profile');
    Profile::factory()->for($this->user)->withPermissions('bypass-authorization')->create();

    expect(fn (): ?Profile => ChangeUserProfile::make()->execute($this->actor, $user, roles: ['editor']))->toThrow(AuthorizationException::class);
});

test('the user holds the new profile after the change', function (): void {
    $this->user->load('profile');

    $profile = ChangeUserProfile::make()->execute($this->actor, $this->user, roles: ['editor']);

    expect($this->user->profile?->id)->toBe($profile?->id);
});

test('a permission that a new role gives is not kept as a direct permission', function (): void {
    Role::findByName('editor')->givePermissionTo('admin.access');

    $profile = ChangeUserProfile::make()->execute($this->actor, $this->user, roles: ['editor'], permissions: ['admin.access', 'admin.controls.users.view']);

    expect(changeUserProfileSnapshot($profile)['permissions'])->toBe(['admin.controls.users.view']);
});

test('a direct permission stays direct when a new role also gives it', function (): void {
    Profile::factory()->for($this->user)->withPermissions('admin.access')->create();
    Role::findByName('editor')->givePermissionTo('admin.access');

    $profile = ChangeUserProfile::make()->execute($this->actor, $this->user, roles: ['editor'], permissions: ['admin.access']);

    expect(changeUserProfileSnapshot($profile)['permissions'])->toBe(['admin.access']);
});

test('a user without a profile gets a first profile', function (): void {
    $profile = ChangeUserProfile::make()->execute($this->actor, $this->user);

    expect(changeUserProfileSnapshot($profile))->toBe([
        'placement' => ['organization_id' => null, 'branch_id' => null, 'department_id' => null, 'position_id' => null],
        'roles' => [],
        'permissions' => [],
    ])->and($this->user->profiles()->count())->toBe(1);
});

test('only a bypass holder can give or remove bypass-authorization or the super-admin role', function (string $argument, string $name): void {
    $give = [$argument => [$name]];
    $remove = [$argument => []];

    expect(fn (): ?Profile => ChangeUserProfile::make()->execute($this->actor, $this->user, ...$give))->toThrow(AuthorizationException::class)
        ->and($this->user->profiles()->count())->toBe(0);

    $this->actor->givePermissionTo('bypass-authorization');

    expect(changeUserProfileSnapshot(ChangeUserProfile::make()->execute($this->actor, $this->user, ...$give))[$argument])->toBe([$name])
        ->and(changeUserProfileSnapshot(ChangeUserProfile::make()->execute($this->actor, $this->user, ...$remove))[$argument])->toBeEmpty();
})->with([
    'bypass-authorization' => ['permissions', 'bypass-authorization'],
    'super-admin' => ['roles', 'super-admin'],
]);

test('nobody can remove their own super-admin role or bypass-authorization', function (string $argument, string $name): void {
    Profile::factory()->for($this->actor)->withRoles('super-admin')->withPermissions('bypass-authorization')->create();

    expect(fn (): ?Profile => ChangeUserProfile::make()->execute($this->actor, $this->actor, ...[$argument => []]))->toThrow(AuthorizationException::class)
        ->and($this->actor->profiles()->count())->toBe(1);
})->with([
    'bypass-authorization' => ['permissions', 'bypass-authorization'],
    'super-admin' => ['roles', 'super-admin'],
]);

test('a user without bypass-authorization cannot change the profile of a user who has it', function (string $argument): void {
    Profile::factory()->for($this->user)->withRoles('super-admin', 'editor')->withPermissions('admin.access')->create();
    $arguments = [
        'placement' => ['placement' => changeUserProfilePlacement(Department::factory()->create())],
        'roles' => ['roles' => ['super-admin', 'viewer']],
        'permissions' => ['permissions' => ['admin.controls.users.view']],
    ][$argument];

    expect(fn (): ?Profile => ChangeUserProfile::make()->execute($this->actor, $this->user, ...$arguments))->toThrow(AuthorizationException::class)
        ->and($this->user->profiles()->count())->toBe(1);
})->with(['placement', 'roles', 'permissions']);

test('the department, branch, and position must fit the organization', function (): void {
    $placement = [...changeUserProfilePlacement(Department::factory()->create()), 'organization_id' => Organization::factory()->create()->id];

    expect(fn (): ?Profile => ChangeUserProfile::make()->execute($this->actor, $this->user, $placement))->toThrow(LogicException::class)
        ->and($this->user->profiles()->count())->toBe(0);
});

test('a non-bypass actor cannot give a custom role that carries bypass-authorization', function (): void {
    Role::query()->create(['name' => 'root'])->givePermissionTo('bypass-authorization');

    expect(fn (): ?Profile => ChangeUserProfile::make()->execute($this->actor, $this->user, roles: ['root']))->toThrow(AuthorizationException::class)
        ->and($this->user->profiles()->count())->toBe(0);
});
