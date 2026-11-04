<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\Role\UserDataTable;
use App\Livewire\Pages\Admin\Controls\Role\Audit;
use App\Livewire\Pages\Admin\Controls\Role\Tabs;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('admin.access', 'admin.controls.roles.view');
    $this->actingAs($this->admin);
    $this->role = Role::query()->create(['name' => 'editor']);
});

test('the users tab shows with view-any on users', function (): void {
    Livewire::test(Tabs::class, ['role' => $this->role])
        ->assertDontSee(__('permission/roles.tabs.users'))
        ->assertDontSeeLivewire(UserDataTable::class);

    $this->admin->givePermissionTo('admin.controls.users.view-any');

    Livewire::test(Tabs::class, ['role' => $this->role])
        ->assertSet('tab', '')
        ->assertSeeLivewire(UserDataTable::class);
});

test('the audit tab shows with view-activities', function (): void {
    Livewire::test(Tabs::class, ['role' => $this->role])
        ->assertDontSee(__('permission/roles.tabs.audit'));

    $this->admin->givePermissionTo('admin.controls.roles.view-activities');

    $this->admin->givePermissionTo('admin.controls.users.view-any');

    Livewire::withQueryParams(['tab' => 'audit'])
        ->test(Tabs::class, ['role' => $this->role])
        ->assertSet('tab', 'audit')
        ->assertSeeLivewire(Audit::class);
});
