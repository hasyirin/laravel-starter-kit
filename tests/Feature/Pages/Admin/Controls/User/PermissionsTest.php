<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Permission;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Admin\Controls\User\Permissions;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo(
        'admin.access',
        'admin.controls.users.view',
        'admin.controls.users.update',
        'admin.controls.users.update-permissions',
    );
    $this->actingAs($this->admin);

    $this->subject = User::factory()->create();
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web'])->givePermissionTo('admin.access');
});

function permissionsTab(User $user): Testable
{
    return Livewire::test(Permissions::class, ['userId' => $user->id]);
}

/**
 * @param  array<string, mixed>  $node
 * @return list<string>
 */
function treeNames(array $node): array
{
    return [
        ...array_column($node['permissions'], 'name'),
        ...collect($node['children'])->flatMap(fn (array $child): array => treeNames($child))->all(),
    ];
}

test('saving permissions makes a new profile and keeps the roles', function (): void {
    Profile::factory()->for($this->subject)->withRoles('editor')->withPermissions('internal.access')->create();

    permissionsTab($this->subject)
        ->call('save', ['admin.horizon.view'])
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === __('user/users.permissions.saved'));

    $profile = $this->subject->fresh()->profile;

    expect($profile->getRoleNames()->all())->toBe(['editor'])
        ->and($profile->getPermissionNames()->all())->toBe(['admin.horizon.view'])
        ->and($this->subject->profiles()->count())->toBe(2);
});

test('permissions from roles are not saved as direct', function (): void {
    Profile::factory()->for($this->subject)->withRoles('editor')->create();

    permissionsTab($this->subject)->call('save', ['admin.access', 'admin.horizon.view']);

    expect($this->subject->fresh()->profile->getPermissionNames()->all())->toBe(['admin.horizon.view']);
});

test('the tab shows the direct permissions and the permissions from roles', function (): void {
    Profile::factory()->for($this->subject)->withRoles('editor')->withPermissions('internal.access')->create();

    permissionsTab($this->subject)
        ->assertSet('direct', ['internal.access'])
        ->assertSet('fromRoles', ['admin.access' => 'editor']);
});

test('bypass-authorization is hidden without bypass', function (): void {
    expect(treeNames(permissionsTab($this->subject)->get('tree')))->not->toContain('bypass-authorization');

    $this->admin->givePermissionTo('bypass-authorization');

    expect(treeNames(permissionsTab($this->subject)->get('tree')))->toContain('bypass-authorization');
});

test('bypass-authorization is not sent to the browser without bypass', function (): void {
    Profile::factory()->for($this->subject)->withRoles('super-admin')->withPermissions('bypass-authorization', 'internal.access')->create();

    permissionsTab($this->subject)
        ->assertSet('direct', ['internal.access'])
        ->assertDontSee('bypass-authorization');
});

test('the permissions of a user with bypass-authorization are read-only without bypass', function (): void {
    Profile::factory()->for($this->subject)->withRoles('super-admin')->withPermissions('bypass-authorization', 'internal.access')->create();

    permissionsTab($this->subject)
        ->assertSet('readOnly', true)
        ->call('save', ['internal.access', 'admin.horizon.view'])
        ->assertForbidden();

    expect($this->subject->fresh()->profile->getPermissionNames()->sort()->values()->all())
        ->toBe(['bypass-authorization', 'internal.access']);
});

test('your own direct bypass-authorization is locked', function (): void {
    $this->admin->givePermissionTo('bypass-authorization');
    Profile::factory()->for($this->admin)->withPermissions('bypass-authorization')->create();

    permissionsTab($this->admin)
        ->assertSet('readOnly', false)
        ->assertSet('locked', ['bypass-authorization']);
});

test('a permission outside the config is refused', function (): void {
    Permission::query()->create(['name' => 'ghost.view', 'guard_name' => 'web']);

    permissionsTab($this->subject)
        ->call('save', ['ghost.view'])
        ->assertHasErrors('permissions.0');

    expect($this->subject->fresh()->profile)->toBeNull();
});

test('the tab is read-only without update-permissions', function (): void {
    $this->admin->revokePermissionTo('admin.controls.users.update-permissions');

    permissionsTab($this->subject)
        ->assertSet('readOnly', true)
        ->call('save', ['admin.horizon.view'])
        ->assertForbidden();

    expect($this->subject->fresh()->profile)->toBeNull();
});
