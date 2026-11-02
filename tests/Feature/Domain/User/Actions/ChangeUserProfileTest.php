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
function placementOf(Department $department): array
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
function snapshotOf(?Profile $profile): array
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
    $placement = placementOf(Department::factory()->create());
    Profile::factory()->for($this->user)->state($placement)->withRoles('editor')->withPermissions('admin.access')->create();

    $newPlacement = placementOf(Department::factory()->create());
    $arguments = [
        'placement' => ['placement' => $newPlacement],
        'roles' => ['roles' => ['viewer']],
        'permissions' => ['permissions' => ['admin.controls.users.view']],
    ][$given];

    $profile = ChangeUserProfile::make()->execute($this->actor, $this->user, ...$arguments);

    expect(snapshotOf($profile))->toBe([
        'placement' => $given === 'placement' ? $newPlacement : $placement,
        'roles' => $given === 'roles' ? ['viewer'] : ['editor'],
        'permissions' => $given === 'permissions' ? ['admin.controls.users.view'] : ['admin.access'],
    ])->and($this->user->profiles()->count())->toBe(2);
})->with(['placement', 'roles', 'permissions']);

test('nothing changes when the sets are the same', function (): void {
    $placement = placementOf(Department::factory()->create());
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

    expect(snapshotOf($profile)['permissions'])->toBe(['admin.controls.users.view'])
        ->and(snapshotOf($profile)['roles'])->toBe(['editor']);
});

test('a user without a profile gets a first profile', function (): void {
    $profile = ChangeUserProfile::make()->execute($this->actor, $this->user);

    expect(snapshotOf($profile))->toBe([
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

    expect(snapshotOf(ChangeUserProfile::make()->execute($this->actor, $this->user, ...$give))[$argument])->toBe([$name])
        ->and(snapshotOf(ChangeUserProfile::make()->execute($this->actor, $this->user, ...$remove))[$argument])->toBeEmpty();
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

test("a change by someone without bypass keeps the user's bypass-authorization and super-admin role", function (): void {
    Profile::factory()->for($this->user)->withRoles('super-admin', 'editor')->withPermissions('bypass-authorization', 'admin.access')->create();

    $profile = ChangeUserProfile::make()->execute($this->actor, $this->user, roles: ['viewer'], permissions: ['admin.controls.users.view']);

    expect(snapshotOf($profile))->toMatchArray([
        'roles' => ['super-admin', 'viewer'],
        'permissions' => ['admin.controls.users.view', 'bypass-authorization'],
    ]);
});

test('the department, branch, and position must fit the organization', function (): void {
    $placement = [...placementOf(Department::factory()->create()), 'organization_id' => Organization::factory()->create()->id];

    expect(fn (): ?Profile => ChangeUserProfile::make()->execute($this->actor, $this->user, $placement))->toThrow(LogicException::class)
        ->and($this->user->profiles()->count())->toBe(0);
});
