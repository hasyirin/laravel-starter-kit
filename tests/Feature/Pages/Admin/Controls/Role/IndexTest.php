<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Admin\Controls\Role\Index;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->create();
    grantPermissions($this->admin, 'admin.access');
    $this->actingAs($this->admin);
});

test('the role list needs view-any', function (): void {
    $this->get(route('admin.controls.roles.index'))->assertForbidden();

    grantPermissions($this->admin, 'admin.controls.roles.view-any');

    $this->get(route('admin.controls.roles.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSee('<title>'.__('permission/roles.title').' - '.config('app.name').'</title>', false);
});

test('the navigation shows the roles item only with view-any', function (): void {
    $this->get(route('admin.dashboard'))->assertDontSee(route('admin.controls.roles.index'));

    grantPermissions($this->admin, 'admin.controls.roles.view-any');

    $this->get(route('admin.dashboard'))->assertSee(route('admin.controls.roles.index'));
});
