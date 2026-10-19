<?php

use App\Domain\Shared\Permission\Models\Permission;
use App\Domain\Shared\Permission\Models\Role;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Spatie\Activitylog\Models\Activity;

function systemRole(): Role
{
    return Role::query()->forceCreate(['name' => 'admin', 'guard_name' => 'web', 'is_system' => true]);
}

test('a new role is not a system role', function (): void {
    expect(Role::query()->create(['name' => 'editor'])->fresh()?->is_system)->toBeFalse();
});

test('the label of a system role is translated', function (): void {
    expect(systemRole()->label)->toBe('Administrator');
});

test('the label of a role that is not a system role is its name', function (): void {
    expect(Role::query()->create(['name' => 'editor'])->label)->toBe('editor');
});

test('role changes are recorded in the activity log', function (): void {
    $role = Role::query()->create(['name' => 'editor']);

    $role->update(['name' => 'author']);

    $activity = Activity::query()->where('event', 'updated')->sole();

    expect($activity->subject_type)->toBe('role')
        ->and($activity->attribute_changes?->get('attributes'))->toBe(['name' => 'author']);
});

test('is_system cannot be mass assigned', function (): void {
    Role::query()->create(['name' => 'editor', 'is_system' => true]);
})->throws(MassAssignmentException::class);

test('a system role cannot be renamed', function (): void {
    systemRole()->update(['name' => 'owner']);
})->throws(LogicException::class, 'System roles cannot be renamed.');

test('a system role cannot be deleted', function (): void {
    $role = systemRole();
    $role->givePermissionTo(Permission::query()->create(['name' => 'admin.access']));

    expect(fn (): ?bool => $role->delete())->toThrow(LogicException::class, 'System roles cannot be deleted.')
        ->and(Role::query()->whereKey($role->id)->exists())->toBeTrue()
        ->and($role->fresh()?->permissions)->toHaveCount(1);
});

test('other attributes of a system role can be updated', function (): void {
    $role = systemRole();

    $role->forceFill(['is_system' => false])->save();
    $role->update(['name' => 'owner']);

    expect($role->fresh()?->name)->toBe('owner');
});

test('a role that is not a system role can be renamed and deleted', function (): void {
    $role = Role::query()->create(['name' => 'editor']);

    $role->update(['name' => 'author']);

    expect($role->delete())->toBeTrue();
});
