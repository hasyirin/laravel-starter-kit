<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Permission;
use App\Domain\Shared\Permission\Models\Role;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Tests\Fixtures\Models\Snapshot;

beforeEach(function (): void {
    Schema::create('snapshots', function (Blueprint $table): void {
        $table->id();
        $table->timestamps();
        $table->softDeletes();
    });

    Relation::morphMap(['snapshot' => Snapshot::class]);
});

function savedSnapshot(): Snapshot
{
    $snapshot = new Snapshot;
    $snapshot->assignRole(Role::query()->create(['name' => 'editor']));
    $snapshot->givePermissionTo(Permission::query()->create(['name' => 'admin.access']));
    $snapshot->save();

    return $snapshot;
}

test('a model keeps the roles and permissions that it gets before its first save', function (string $roleMethod, string $permissionMethod, string $saveMethod): void {
    $role = Role::query()->create(['name' => 'editor']);
    $permission = Permission::query()->create(['name' => 'admin.access']);
    $snapshot = new Snapshot;

    $snapshot->{$roleMethod}($role);
    $snapshot->{$permissionMethod}($permission);
    $snapshot->{$saveMethod}();

    expect($snapshot->fresh()?->getRoleNames()->all())->toBe(['editor'])
        ->and($snapshot->fresh()?->getPermissionNames()->all())->toBe(['admin.access']);
})->with([
    'assign' => ['assignRole', 'givePermissionTo'],
    'sync' => ['syncRoles', 'syncPermissions'],
])->with([
    'with model events' => 'save',
    'without model events' => 'saveQuietly',
]);

test('a model keeps the roles and permissions from every call before its first save', function (): void {
    $snapshot = new Snapshot;

    $snapshot->assignRole(Role::query()->create(['name' => 'editor']));
    $snapshot->assignRole(Role::query()->create(['name' => 'author']));
    $snapshot->givePermissionTo(Permission::query()->create(['name' => 'admin.access']));
    $snapshot->givePermissionTo(Permission::query()->create(['name' => 'admin.horizon.view']));
    $snapshot->save();

    expect($snapshot->fresh()?->getRoleNames()->sort()->values()->all())->toBe(['author', 'editor'])
        ->and($snapshot->fresh()?->getPermissionNames()->sort()->values()->all())->toBe(['admin.access', 'admin.horizon.view']);
});

test('syncing before the first save replaces the roles and permissions given before', function (): void {
    $snapshot = new Snapshot;
    $snapshot->assignRole(Role::query()->create(['name' => 'editor']));
    $snapshot->givePermissionTo(Permission::query()->create(['name' => 'admin.access']));

    $snapshot->syncRoles(Role::query()->create(['name' => 'author']));
    $snapshot->syncPermissions(Permission::query()->create(['name' => 'admin.horizon.view']));
    $snapshot->save();

    expect($snapshot->fresh()?->getRoleNames()->all())->toBe(['author'])
        ->and($snapshot->fresh()?->getPermissionNames()->all())->toBe(['admin.horizon.view']);
});

test('saving a model with roles returns false when a listener stops the save', function (): void {
    Snapshot::saving(fn (): bool => false);
    $snapshot = new Snapshot;
    $snapshot->assignRole(Role::query()->create(['name' => 'editor']));

    expect($snapshot->save())->toBeFalse()
        ->and(Snapshot::query()->exists())->toBeFalse();
});

test('a model is not saved when one of its roles does not exist', function (): void {
    $snapshot = new Snapshot;
    $snapshot->assignRole('editor');

    expect(fn (): bool => $snapshot->save())->toThrow(RoleDoesNotExist::class)
        ->and(Snapshot::query()->exists())->toBeFalse();
});

test('the roles and permissions of a model cannot change after its first save', function (string $method, Role|Permission|string $change): void {
    $snapshot = savedSnapshot();

    expect(fn (): mixed => $snapshot->{$method}($change))->toThrow(LogicException::class, 'Roles and permissions can only be given before the first save.')
        ->and($snapshot->fresh()?->getRoleNames()->all())->toBe(['editor'])
        ->and($snapshot->fresh()?->getPermissionNames()->all())->toBe(['admin.access']);
})->with([
    'assign a role' => ['assignRole', fn (): Role => Role::query()->create(['name' => 'author'])],
    'remove a role' => ['removeRole', 'editor'],
    'sync the roles' => ['syncRoles', fn (): Role => Role::query()->create(['name' => 'author'])],
    'give a permission' => ['givePermissionTo', fn (): Permission => Permission::query()->create(['name' => 'admin.horizon.view'])],
    'revoke a permission' => ['revokePermissionTo', 'admin.access'],
    'sync the permissions' => ['syncPermissions', fn (): Permission => Permission::query()->create(['name' => 'admin.horizon.view'])],
]);

test('roles and permissions cannot be removed even before the first save', function (string $method, Role|Permission $existing): void {
    $snapshot = new Snapshot;

    expect(fn (): mixed => $snapshot->{$method}($existing))->toThrow(LogicException::class, 'Roles and permissions can only be given before the first save.');
})->with([
    'remove a role' => ['removeRole', fn (): Role => Role::query()->create(['name' => 'editor'])],
    'revoke a permission' => ['revokePermissionTo', fn (): Permission => Permission::query()->create(['name' => 'admin.access'])],
]);
