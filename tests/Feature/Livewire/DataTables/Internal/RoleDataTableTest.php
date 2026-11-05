<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\RoleDataTable;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.roles.view-any', 'internal.controls.roles.create', 'internal.controls.roles.delete');
    $this->actingAs($this->actor);
    $this->role = Role::query()->create(['name' => 'editor']);
});

test('the list shows the label of each role', function (): void {
    $this->seed(RoleSeeder::class);

    Livewire::test(RoleDataTable::class)
        ->assertCanSeeTableRecords([$this->role, Role::findByName('system-admin')])
        ->assertSee('editor')
        ->assertSee(__('user/roles.system-admin'));
});

test('the table refuses a user without view-any', function (): void {
    revokePermissions($this->actor, 'internal.controls.roles.view-any');

    Livewire::test(RoleDataTable::class)->assertForbidden();
});

test('the create action goes to the create page', function (): void {
    Livewire::test(RoleDataTable::class)
        ->callAction(TestAction::make('create')->table())
        ->assertRedirect(route('internal.controls.roles.create'));
});

test('the row click opens the role', function (): void {
    Livewire::test(RoleDataTable::class)
        ->callAction(TestAction::make('view')->table($this->role))
        ->assertRedirect(route('internal.controls.roles.show', $this->role));
});

test('the delete action deletes a role that no profile holds', function (): void {
    Livewire::test(RoleDataTable::class)
        ->callAction(TestAction::make('delete')->table($this->role));

    expect(Role::query()->whereKey($this->role->id)->exists())->toBeFalse();
});

test('the delete action is hidden without the delete permission', function (): void {
    revokePermissions($this->actor, 'internal.controls.roles.delete');

    Livewire::test(RoleDataTable::class)->assertActionHidden(TestAction::make('delete')->table($this->role));
});

test('the delete action is hidden on a system role and a role that a profile holds, also with bypass', function (): void {
    $this->seed(RoleSeeder::class);
    grantPermissions($this->actor, 'bypass-authorization');
    Profile::factory()->for(User::factory())->withRoles('editor')->create();

    Livewire::test(RoleDataTable::class)
        ->assertActionHidden(TestAction::make('delete')->table($this->role))
        ->assertActionHidden(TestAction::make('delete')->table(Role::findByName('system-admin')));
});
