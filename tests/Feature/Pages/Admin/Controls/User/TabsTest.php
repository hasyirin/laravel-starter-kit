<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\User\RoleDataTable;
use App\Livewire\Pages\Admin\Controls\User\Permissions;
use App\Livewire\Pages\Admin\Controls\User\Tabs;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('admin.access', 'admin.controls.users.view');
    $this->actingAs($this->admin);
    $this->subject = User::factory()->create();
});

test('the roles tab is the default tab', function (): void {
    Livewire::test(Tabs::class, ['userId' => $this->subject->id])
        ->assertSet('tab', '')
        ->assertSeeLivewire(RoleDataTable::class);
});

test('switching to the permissions tab shows it', function (): void {
    Livewire::test(Tabs::class, ['userId' => $this->subject->id])
        ->call('switchTab', 'permissions')
        ->assertSet('tab', 'permissions')
        ->assertSeeLivewire(Permissions::class);
});

test('the tab is kept in the URL', function (): void {
    Livewire::withQueryParams(['tab' => 'permissions'])
        ->test(Tabs::class, ['userId' => $this->subject->id])
        ->assertSet('tab', 'permissions')
        ->assertSeeLivewire(Permissions::class);
});

test('the show page mounts the tabs below the card', function (): void {
    $this->get(route('admin.controls.users.show', $this->subject))
        ->assertOk()
        ->assertSeeLivewire(Tabs::class);
});
