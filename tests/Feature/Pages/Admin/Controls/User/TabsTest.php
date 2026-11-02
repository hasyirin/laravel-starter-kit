<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\User\RoleDataTable;
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

test('the tab is kept in the URL', function (): void {
    $component = Livewire::test(Tabs::class, ['userId' => $this->subject->id]);

    expect($component->effects['url']['tab'])->toMatchArray(['as' => 'tab']);
});

test('the show page mounts the tabs below the card', function (): void {
    $this->get(route('admin.controls.users.show', $this->subject))
        ->assertOk()
        ->assertSeeLivewire(Tabs::class);
});
