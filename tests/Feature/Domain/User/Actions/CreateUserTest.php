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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;

test('creating a user gives them a first profile with the placement', function (): void {
    $department = Department::factory()->create();
    $placement = [
        'organization_id' => $department->organization_id,
        'branch_id' => Branch::factory()->for($department->organization)->create()->id,
        'department_id' => $department->id,
        'position_id' => Position::factory()->forDepartment($department)->create()->id,
    ];

    $user = CreateUser::make()->execute(User::factory()->create(), 'Ada Lovelace', 'ada@example.com', 'secret-password', $placement);

    expect($user->exists)->toBeTrue()
        ->and($user->name)->toBe('Ada Lovelace')
        ->and($user->email)->toBe('ada@example.com')
        ->and(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($user->profiles()->count())->toBe(1)
        ->and($user->profile()->first()?->only(collect($placement)->keys()->all()))->toBe($placement);
});

test('the first profile gets the given roles and permissions', function (): void {
    $this->seed(PermissionSeeder::class);
    Role::query()->create(['name' => 'editor']);
    $placement = ['organization_id' => null, 'branch_id' => null, 'department_id' => null, 'position_id' => null];

    $user = CreateUser::make()->execute(User::factory()->create(), 'Ada Lovelace', 'ada@example.com', 'secret-password', $placement, ['editor'], ['internal.access']);

    $profile = $user->profile()->first();
    expect($user->profiles()->count())->toBe(1)
        ->and($profile?->getRoleNames()->all())->toBe(['editor'])
        ->and($profile?->getPermissionNames()->all())->toBe(['internal.access']);
});

test('a refused bypass role leaves no user behind', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    $placement = ['organization_id' => null, 'branch_id' => null, 'department_id' => null, 'position_id' => null];

    expect(fn (): User => CreateUser::make()->execute(User::factory()->create(), 'Ada Lovelace', 'ada@example.com', 'secret-password', $placement, ['system-admin']))
        ->toThrow(AuthorizationException::class);

    $this->assertDatabaseMissing('users', ['email' => 'ada@example.com']);
});
