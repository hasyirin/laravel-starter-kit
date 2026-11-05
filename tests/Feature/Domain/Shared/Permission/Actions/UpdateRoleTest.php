<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Actions\UpdateRole;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    $this->actingAs($this->actor);
    $this->role = Role::query()->create(['name' => 'editor']);
    $this->role->givePermissionTo('admin.access');
    Activity::query()->delete();
});

test('the name and the permissions change in one logged update', function (): void {
    UpdateRole::make()->execute($this->actor, $this->role, 'author', ['admin.controls.users.view', 'admin.access']);

    $entry = Activity::query()->sole();

    expect($this->role->fresh()?->name)->toBe('author')
        ->and($this->role->fresh()?->getPermissionNames()->sort()->values()->all())->toBe(['admin.access', 'admin.controls.users.view'])
        ->and($entry->event)->toBe('updated')
        ->and($entry->causer?->is($this->actor))->toBeTrue()
        ->and($entry->attribute_changes?->get('old'))->toBe(['name' => 'editor', 'permissions' => ['admin.access']])
        ->and($entry->attribute_changes?->get('attributes'))->toBe(['name' => 'author', 'permissions' => ['admin.access', 'admin.controls.users.view']]);
});

test('a change to the permissions alone is logged', function (): void {
    UpdateRole::make()->execute($this->actor, $this->role, null, []);

    expect(Activity::query()->sole()->attribute_changes?->get('old'))->toBe(['permissions' => ['admin.access']]);
});

test('nothing is logged when nothing changes', function (): void {
    UpdateRole::make()->execute($this->actor, $this->role, 'editor', ['admin.access']);

    expect(Activity::query()->count())->toBe(0);
});

test('the permissions stay when they are not given', function (): void {
    UpdateRole::make()->execute($this->actor, $this->role, 'author');

    expect($this->role->fresh()?->getPermissionNames()->all())->toBe(['admin.access']);
});

test('a role with bypass-authorization cannot be changed without bypass', function (): void {
    $this->role->givePermissionTo('bypass-authorization');

    expect(fn (): Role => UpdateRole::make()->execute($this->actor, $this->role, 'author'))->toThrow(AuthorizationException::class)
        ->and($this->role->fresh()?->name)->toBe('editor');
});

test('bypass-authorization cannot be added without bypass', function (): void {
    expect(fn (): Role => UpdateRole::make()->execute($this->actor, $this->role, null, ['admin.access', 'bypass-authorization']))->toThrow(AuthorizationException::class);
});

test('bypass-authorization cannot be removed from a role that you hold', function (): void {
    grantPermissions($this->actor, 'bypass-authorization');
    $this->role->givePermissionTo('bypass-authorization');
    grantRoles($this->actor, $this->role);

    expect(fn (): Role => UpdateRole::make()->execute($this->actor, $this->role, null, ['admin.access']))->toThrow(AuthorizationException::class)
        ->and($this->role->fresh()?->hasPermissionTo('bypass-authorization'))->toBeTrue();
});

test('bypass-authorization can be removed from another role with bypass', function (): void {
    grantPermissions($this->actor, 'bypass-authorization');
    $this->role->givePermissionTo('bypass-authorization');

    UpdateRole::make()->execute($this->actor, $this->role, null, ['admin.access']);

    expect($this->role->fresh()?->hasPermissionTo('bypass-authorization'))->toBeFalse();
});
