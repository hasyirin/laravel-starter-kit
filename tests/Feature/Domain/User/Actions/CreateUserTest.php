<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Actions\CreateUser;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Placement;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;

test('creating a user gives them a first profile with the placement', function (): void {
    $department = Department::factory()->create();
    $placement = new Placement(
        organizationId: $department->organization_id,
        branchId: Branch::factory()->for($department->organization)->create()->id,
        departmentId: $department->id,
        positionId: Position::factory()->forDepartment($department)->create()->id,
    );

    $user = CreateUser::make()->execute(
        actor: User::factory()->create(),
        name: 'Ada Lovelace',
        email: 'ada@example.com',
        password: 'secret-password',
        placement: $placement,
    );

    expect($user->exists)->toBeTrue()
        ->and($user->name)->toBe('Ada Lovelace')
        ->and($user->email)->toBe('ada@example.com')
        ->and(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($user->profiles()->count())->toBe(1)
        ->and(Placement::of($user->profile()->first()))->toEqual($placement);
});

test('the first profile gets the given roles and permissions', function (): void {
    $this->seed(PermissionSeeder::class);
    Role::query()->create(['name' => 'editor']);

    $user = CreateUser::make()->execute(
        actor: User::factory()->create(),
        name: 'Ada Lovelace',
        email: 'ada@example.com',
        password: 'secret-password',
        placement: new Placement,
        roles: ['editor'],
        permissions: ['internal.access'],
    );

    $profile = $user->profile()->first();
    expect($user->profiles()->count())->toBe(1)
        ->and($profile?->getRoleNames()->all())->toBe(['editor'])
        ->and($profile?->getPermissionNames()->all())->toBe(['internal.access']);
});

test('a refused bypass role leaves no user behind', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    expect(fn (): User => CreateUser::make()->execute(
        actor: User::factory()->create(),
        name: 'Ada Lovelace',
        email: 'ada@example.com',
        password: 'secret-password',
        placement: new Placement,
        roles: ['system-admin'],
    ))
        ->toThrow(AuthorizationException::class);

    $this->assertDatabaseMissing('users', ['email' => 'ada@example.com']);
});
