<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\UserDataTable;
use App\Livewire\Pages\Internal\Controls\User\ImpersonateModal;
use App\Livewire\Pages\Internal\Controls\User\Index;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

test('guests are redirected to the login page', function (): void {
    $this->get(route('internal.controls.users.index'))->assertRedirect(route('login'));
});

test('the user list needs view-any', function (): void {
    $user = User::factory()->create();
    grantPermissions($user, 'internal.access');
    $this->actingAs($user);

    $this->get(route('internal.controls.users.index'))->assertForbidden();

    grantPermissions($user, 'internal.controls.users.view-any');

    $this->get(route('internal.controls.users.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSee('<title>'.__('user/users.title').' - '.config('app.name').'</title>', false);
});

test('the navigation shows the users item only with view-any', function (): void {
    $user = User::factory()->create();
    grantPermissions($user, 'internal.access');
    $this->actingAs($user);

    $this->get(route('internal.dashboard'))->assertDontSee(route('internal.controls.users.index'));

    grantPermissions($user, 'internal.controls.users.view-any');

    $this->get(route('internal.dashboard'))->assertSee(route('internal.controls.users.index'));
});

test('the user table refuses a user without view-any', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::withoutLazyLoading()->test(UserDataTable::class)->assertForbidden();

    grantPermissions($user, 'internal.controls.users.view-any');

    Livewire::withoutLazyLoading()->test(UserDataTable::class)->assertOk();
});

test('the user list holds the impersonate modal', function (): void {
    $user = User::factory()->create();
    grantPermissions($user, 'internal.access', 'internal.controls.users.view-any');

    $this->actingAs($user)->get(route('internal.controls.users.index'))->assertSeeLivewire(ImpersonateModal::class);
});
