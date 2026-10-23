<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Permission;
use App\Domain\Shared\Permission\Models\Role;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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

test('a model keeps the roles and permissions that it gets before its first save', function (string $roleMethod, string $permissionMethod): void {
    $role = Role::query()->create(['name' => 'editor']);
    $permission = Permission::query()->create(['name' => 'admin.access']);
    $snapshot = new Snapshot;

    $snapshot->{$roleMethod}($role);
    $snapshot->{$permissionMethod}($permission);
    $snapshot->save();

    expect($snapshot->fresh()?->getRoleNames()->all())->toBe(['editor'])
        ->and($snapshot->fresh()?->getPermissionNames()->all())->toBe(['admin.access']);
})->with([
    'assign' => ['assignRole', 'givePermissionTo'],
    'sync' => ['syncRoles', 'syncPermissions'],
]);

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
