<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\UserDataTable;
use App\Livewire\Pages\Admin\Controls\User\Index;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

test('guests are redirected to the login page', function (): void {
    $this->get(route('admin.controls.users.index'))->assertRedirect(route('login'));
});

test('the user list needs view-any', function (): void {
    $user = User::factory()->create();
    grantPermissions($user, 'admin.access');
    $this->actingAs($user);

    $this->get(route('admin.controls.users.index'))->assertForbidden();

    grantPermissions($user, 'admin.controls.users.view-any');

    $this->get(route('admin.controls.users.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSee('<title>'.__('user/users.title').' - '.config('app.name').'</title>', false);
});

test('the navigation shows the users item only with view-any', function (): void {
    $user = User::factory()->create();
    grantPermissions($user, 'admin.access');
    $this->actingAs($user);

    $this->get(route('admin.dashboard'))->assertDontSee(route('admin.controls.users.index'));

    grantPermissions($user, 'admin.controls.users.view-any');

    $this->get(route('admin.dashboard'))->assertSee(route('admin.controls.users.index'));
});

test('the user table refuses a user without view-any', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::withoutLazyLoading()->test(UserDataTable::class)->assertForbidden();

    grantPermissions($user, 'admin.controls.users.view-any');

    Livewire::withoutLazyLoading()->test(UserDataTable::class)->assertOk();
});
