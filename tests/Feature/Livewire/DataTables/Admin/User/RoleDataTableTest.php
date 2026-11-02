<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\User\RoleDataTable;
use Filament\Actions\Testing\TestAction;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo(
        'admin.access',
        'admin.controls.users.view',
        'admin.controls.users.update',
        'admin.controls.users.update-roles',
    );
    $this->actingAs($this->admin);

    $this->subject = User::factory()->create();
    $this->editor = Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);
    $this->viewer = Role::query()->create(['name' => 'viewer', 'guard_name' => 'web']);
});

function roleTable(User $user): Testable
{
    return Livewire::test(RoleDataTable::class, ['userId' => $user->id]);
}

test('the roles tab lists the roles of the current profile', function (): void {
    Profile::factory()->for($this->subject)->withRoles('editor')->create();
    Profile::factory()->for($this->subject)->withRoles('viewer')->create();

    roleTable($this->subject)
        ->assertCanSeeTableRecords([$this->viewer])
        ->assertCanNotSeeTableRecords([$this->editor]);
});

test('the roles tab is empty without a profile', function (): void {
    roleTable($this->subject)->assertCountTableRecords(0);
});

test('attaching roles makes a new profile with them', function (): void {
    Profile::factory()->for($this->subject)->withRoles('editor')->withPermissions('admin.access')->create();

    roleTable($this->subject)
        ->callAction(TestAction::make('attach')->table(), ['roles' => ['viewer']])
        ->assertHasNoActionErrors();

    $profile = $this->subject->fresh()->profile;

    expect($profile->getRoleNames()->sort()->values()->all())->toBe(['editor', 'viewer'])
        ->and($profile->getPermissionNames()->all())->toBe(['admin.access'])
        ->and($this->subject->profiles()->count())->toBe(2);
});

test('detaching a role keeps the other roles and the permissions', function (): void {
    Profile::factory()->for($this->subject)->withRoles('editor', 'viewer')->withPermissions('admin.access')->create();

    roleTable($this->subject)->callAction(TestAction::make('detach')->table($this->viewer));

    $profile = $this->subject->fresh()->profile;

    expect($profile->getRoleNames()->all())->toBe(['editor'])
        ->and($profile->getPermissionNames()->all())->toBe(['admin.access']);
});

test('the roles tab is read-only without update-roles', function (): void {
    Profile::factory()->for($this->subject)->withRoles('editor')->create();
    $this->admin->revokePermissionTo('admin.controls.users.update-roles');

    roleTable($this->subject)
        ->assertCanSeeTableRecords([$this->editor])
        ->assertActionHidden(TestAction::make('attach')->table())
        ->assertActionHidden(TestAction::make('detach')->table($this->editor));
});

test('your own roles are read-only without bypass', function (): void {
    Profile::factory()->for($this->admin)->withRoles('editor')->create();

    roleTable($this->admin)
        ->assertActionHidden(TestAction::make('attach')->table())
        ->assertActionHidden(TestAction::make('detach')->table($this->editor));
});

test('the detach action is hidden on your own bypass role', function (): void {
    $this->admin->givePermissionTo('bypass-authorization');
    Profile::factory()->for($this->admin)->withRoles('super-admin', 'editor')->create();
    $superAdmin = Role::query()->where('name', 'super-admin')->firstOrFail();

    roleTable($this->admin)
        ->assertActionHidden(TestAction::make('detach')->table($superAdmin))
        ->assertActionVisible(TestAction::make('detach')->table($this->editor));
});

test('the detach action is hidden on a bypass role without bypass', function (): void {
    $bypassRole = Role::query()->create(['name' => 'root', 'guard_name' => 'web']);
    $bypassRole->givePermissionTo('bypass-authorization');
    Profile::factory()->for($this->subject)->withRoles('root')->create();

    roleTable($this->subject)->assertActionHidden(TestAction::make('detach')->table($bypassRole));
});

test('roles that carry bypass are not offered without bypass', function (string $role): void {
    Role::query()->create(['name' => 'root', 'guard_name' => 'web'])->givePermissionTo('bypass-authorization');

    roleTable($this->subject)
        ->callAction(TestAction::make('attach')->table(), ['roles' => [$role]])
        ->assertHasActionErrors(['roles']);
})->with(['super-admin', 'root']);

test('a role without bypass is offered', function (): void {
    roleTable($this->subject)
        ->callAction(TestAction::make('attach')->table(), ['roles' => ['editor']])
        ->assertHasNoActionErrors();
});

test('the super-admin role is offered with bypass', function (): void {
    $this->admin->givePermissionTo('bypass-authorization');

    roleTable($this->subject)
        ->callAction(TestAction::make('attach')->table(), ['roles' => ['super-admin']])
        ->assertHasNoActionErrors();

    expect($this->subject->fresh()->profile->hasRole('super-admin'))->toBeTrue();
});

test('roles the user already has are not offered', function (): void {
    Profile::factory()->for($this->subject)->withRoles('editor')->create();

    roleTable($this->subject)
        ->callAction(TestAction::make('attach')->table(), ['roles' => ['editor']])
        ->assertHasActionErrors(['roles']);
});

test('attaching a role that does not exist is refused', function (): void {
    roleTable($this->subject)
        ->callAction(TestAction::make('attach')->table(), ['roles' => ['ghost']])
        ->assertHasActionErrors(['roles']);

    expect($this->subject->fresh()->profile)->toBeNull();
});
