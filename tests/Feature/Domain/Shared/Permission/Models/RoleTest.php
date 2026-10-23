<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Permission;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Spatie\Activitylog\Models\Activity;

function systemRole(): Role
{
    return Role::query()->forceCreate(['name' => 'super-admin', 'guard_name' => 'web', 'is_system' => true]);
}

test('a new role is not a system role', function (): void {
    expect(Role::query()->create(['name' => 'editor'])->fresh()?->is_system)->toBeFalse();
});

test('the label of a system role is translated', function (): void {
    expect(systemRole()->label)->toBe('Super Administrator');
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

test('a role that a profile has cannot be deleted', function (): void {
    $role = Role::query()->create(['name' => 'editor']);
    $profile = Profile::factory()->withRoles($role)->create();

    expect(fn (): ?bool => $role->delete())->toThrow(LogicException::class, 'Roles assigned to profiles cannot be deleted.')
        ->and(Role::query()->whereKey($role->id)->exists())->toBeTrue()
        ->and($profile->fresh()?->roles)->toHaveCount(1);
});

test('the profiles of a force deleted user do not keep a role from being deleted', function (): void {
    $role = Role::query()->create(['name' => 'editor']);
    Profile::factory()->withRoles($role)->create()->user->forceDelete();

    expect($role->delete())->toBeTrue();
});

test('a role cannot be assigned to a profile', function (): void {
    $role = Role::query()->create(['name' => 'editor']);
    $profile = Profile::factory()->create();

    expect(fn (): Role => $role->assignToModels($profile))->toThrow(LogicException::class, 'The roles of a profile cannot be changed.')
        ->and($profile->fresh()?->roles)->toBeEmpty();
});

test('a role cannot be removed from a profile', function (): void {
    $role = Role::query()->create(['name' => 'editor']);
    $profile = Profile::factory()->withRoles($role)->create();

    expect(fn (): Role => $role->removeFromModels($profile))->toThrow(LogicException::class, 'The roles of a profile cannot be changed.')
        ->and($profile->fresh()?->roles)->toHaveCount(1);
});

test('a role that a profile has cannot be synced to other models', function (): void {
    $role = Role::query()->create(['name' => 'editor']);
    $profile = Profile::factory()->withRoles($role)->create();
    $user = User::factory()->create();

    expect(fn (): Role => $role->syncModels($user))->toThrow(LogicException::class, 'The roles of a profile cannot be changed.')
        ->and($profile->fresh()?->roles)->toHaveCount(1)
        ->and($user->fresh()?->roles)->toBeEmpty();
});

test('a role cannot be synced to a profile', function (): void {
    $role = Role::query()->create(['name' => 'editor']);
    $profile = Profile::factory()->create();
    $user = User::factory()->create();
    $user->assignRole($role);

    expect(fn (): Role => $role->syncModels($profile))->toThrow(LogicException::class, 'The roles of a profile cannot be changed.')
        ->and($user->fresh()?->roles)->toHaveCount(1)
        ->and($profile->fresh()?->roles)->toBeEmpty();
});

test('a role that no profile has can be synced to users', function (): void {
    $role = Role::query()->create(['name' => 'editor']);
    $user = User::factory()->create();
    $former = User::factory()->create();
    $former->assignRole($role);

    $role->syncModels($user);

    expect($former->fresh()?->roles)->toBeEmpty()
        ->and($user->fresh()?->roles)->toHaveCount(1);
});
