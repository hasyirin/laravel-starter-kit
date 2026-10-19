<?php

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Policies\RolePolicy;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Tests\Fixtures\Policies\ArchivedItemPolicy;

test('the path uses the root, the prefix, the plural model name, and the action', function (): void {
    expect(new RolePolicy()->path('update-permissions'))->toBe('system.permissions.admin.controls.roles.update-permissions');
});

test('the path uses the model property when it is set', function (): void {
    expect(new ArchivedItemPolicy()->path('view'))->toBe('system.permissions.archive.items.view');
});

function rolePermissionsPolicy(): object
{
    return new class extends Policy
    {
        protected static string $prefix = 'admin.controls';

        protected static ?string $model = Role::class;

        public function any(User $user, mixed ...$actions): bool
        {
            return $this->hasAnyPermission($user, ...$actions);
        }

        public function all(User $user, mixed ...$actions): bool
        {
            return $this->hasAllPermissions($user, ...$actions);
        }
    };
}

test('has any permission accepts strings, arrays, and collections', function (mixed $actions): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();

    expect(rolePermissionsPolicy()->any($user, ...$actions))->toBeFalse();

    $user->givePermissionTo('admin.controls.roles.view');

    expect(rolePermissionsPolicy()->any($user, ...$actions))->toBeTrue();
})->with([
    'strings' => [['view-any', 'view']],
    'array' => [[['view-any', 'view']]],
    'collection' => fn (): array => [collect(['view-any', 'view'])],
    'mixed' => fn (): array => ['view-any', collect(['create']), ['view']],
]);

test('has all permissions needs every permission', function (mixed $actions): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->givePermissionTo('admin.controls.roles.view');

    expect(rolePermissionsPolicy()->all($user, ...$actions))->toBeFalse();

    $user->givePermissionTo('admin.controls.roles.view-any');

    expect(rolePermissionsPolicy()->all($user, ...$actions))->toBeTrue();
})->with([
    'strings' => [['view-any', 'view']],
    'array' => [[['view-any', 'view']]],
    'collection' => fn (): array => [collect(['view-any', 'view'])],
]);
