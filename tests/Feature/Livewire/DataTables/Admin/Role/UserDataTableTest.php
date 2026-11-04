<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\Role\UserDataTable;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('admin.access', 'admin.controls.roles.view', 'admin.controls.users.view-any');
    $this->actingAs($this->admin);
    $this->role = Role::query()->create(['name' => 'editor']);
});

test('the list shows the users with the role in their active profile or directly', function (): void {
    $viaProfile = User::factory()->create();
    Profile::factory()->for($viaProfile)->withRoles('editor')->create();
    $direct = User::factory()->create();
    $direct->assignRole($this->role);

    $formerly = User::factory()->create();
    Profile::factory()->for($formerly)->withRoles('editor')->create(['created_at' => now()->subDay()]);
    Profile::factory()->for($formerly)->create();

    Livewire::test(UserDataTable::class, ['role' => $this->role])
        ->assertCanSeeTableRecords([$viaProfile, $direct])
        ->assertCanNotSeeTableRecords([$formerly, $this->admin]);
});

test('the table needs view-any on users', function (): void {
    $this->admin->revokePermissionTo('admin.controls.users.view-any');

    Livewire::test(UserDataTable::class, ['role' => $this->role])->assertForbidden();
});

test('the row click opens the user under the role', function (): void {
    $user = User::factory()->create();
    $user->assignRole($this->role);

    Livewire::test(UserDataTable::class, ['role' => $this->role])
        ->callAction(TestAction::make('view')->table($user))
        ->assertRedirect(route('admin.controls.roles.users.show', [$this->role, $user]));
});
