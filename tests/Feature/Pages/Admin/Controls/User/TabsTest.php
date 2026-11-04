<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\User\PasskeyDataTable;
use App\Livewire\Pages\Admin\Controls\User\Audit;
use App\Livewire\Pages\Admin\Controls\User\Tabs;
use Laravel\Fortify\Features;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('admin.access', 'admin.controls.users.view');
    $this->actingAs($this->admin);
    $this->subject = User::factory()->create();
});

test('the passkeys tab is the default tab when passkeys are on', function (): void {
    Livewire::test(Tabs::class, ['user' => $this->subject])
        ->assertSet('tab', '')
        ->assertSeeLivewire(PasskeyDataTable::class);
});

test('the tab is kept in the URL', function (): void {
    $this->admin->givePermissionTo('admin.controls.users.view-activities');

    Livewire::withQueryParams(['tab' => 'audit'])
        ->test(Tabs::class, ['user' => $this->subject])
        ->assertSet('tab', 'audit')
        ->assertSeeLivewire(Audit::class);
});

test('the show page mounts the tabs below the form', function (): void {
    $this->get(route('admin.controls.users.show', $this->subject))
        ->assertOk()
        ->assertSeeLivewire(Tabs::class);
});

test('the passkeys tab is hidden when passkeys are off', function (): void {
    config()->set('fortify.features', []);
    $this->admin->givePermissionTo('admin.controls.users.view-activities');

    expect(Features::canManagePasskeys())->toBeFalse();

    Livewire::test(Tabs::class, ['user' => $this->subject])
        ->assertDontSeeLivewire(PasskeyDataTable::class)
        ->assertDontSee(__('user/users.tabs.passkeys'));
});

test('the audit tab shows with view-activities', function (): void {
    $this->admin->givePermissionTo('admin.controls.users.view-activities');

    Livewire::test(Tabs::class, ['user' => $this->subject])
        ->call('switchTab', 'audit')
        ->assertSet('tab', 'audit')
        ->assertSeeLivewire(Audit::class);
});

test('the audit tab is hidden without view-activities', function (): void {
    Livewire::test(Tabs::class, ['user' => $this->subject])
        ->assertDontSee(__('user/users.tabs.audit'));
});
