<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\UserDataTable;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->actor = User::factory()->create();
    grantPermissions($this->actor,
        'system.access',
        'admin.controls.users.view-any',
        'admin.controls.users.view',
        'admin.controls.users.create',
        'admin.controls.users.delete',
    );
    $this->actingAs($this->actor);
});

test('the list shows name, email, and roles', function (): void {
    $user = User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.test']);
    Profile::factory()->for($user)->withRoles('system-admin')->create();

    Livewire::test(UserDataTable::class)
        ->assertCanSeeTableRecords([$user])
        ->assertSee('Ada Lovelace')
        ->assertSee('ada@example.test')
        ->assertSee('System Administrator');
});

test('a deleted user is not listed', function (): void {
    $user = User::factory()->create();
    $user->delete();

    Livewire::test(UserDataTable::class)->assertCanNotSeeTableRecords([$user]);
});

test('the delete action is hidden on your own row', function (): void {
    $other = User::factory()->create();

    Livewire::test(UserDataTable::class)
        ->assertActionVisible(TestAction::make('delete')->table($other))
        ->assertActionHidden(TestAction::make('delete')->table($this->actor));
});

test('the delete action is hidden without the delete permission', function (): void {
    $other = User::factory()->create();
    revokePermissions($this->actor, 'admin.controls.users.delete');

    Livewire::test(UserDataTable::class)
        ->assertActionHidden(TestAction::make('delete')->table($other));
});

test('the delete action soft deletes the user', function (): void {
    $other = User::factory()->create();

    Livewire::test(UserDataTable::class)
        ->callAction(TestAction::make('delete')->table($other));

    expect($other->fresh()->trashed())->toBeTrue();
});

test('the create action goes to the create page', function (): void {
    Livewire::test(UserDataTable::class)
        ->callAction(TestAction::make('create')->table())
        ->assertRedirect(route('admin.controls.users.create'));
});

test('the row click opens the user', function (): void {
    $other = User::factory()->create();

    Livewire::test(UserDataTable::class)
        ->callAction(TestAction::make('view')->table($other))
        ->assertRedirect(route('admin.controls.users.show', $other));
});
