<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\User\PasskeyDataTable;
use App\Livewire\Pages\Internal\Controls\User\Audit;
use App\Livewire\Pages\Internal\Controls\User\Tabs;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.users.view');
    $this->actingAs($this->actor);
    $this->subject = User::factory()->create();
});

test('the passkeys tab is the default tab when passkeys are on', function (): void {
    $this->skipUnlessFeature(Feature::Passkeys);

    Livewire::test(Tabs::class, ['user' => $this->subject])
        ->assertSet('tab', '')
        ->assertSeeLivewire(PasskeyDataTable::class);
});

test('the tab is kept in the URL', function (): void {
    $this->skipUnlessFeature(Feature::Passkeys);

    grantPermissions($this->actor, 'internal.controls.users.view-audit-log');

    Livewire::withQueryParams(['tab' => 'audit'])
        ->test(Tabs::class, ['user' => $this->subject])
        ->assertSet('tab', 'audit')
        ->assertSeeLivewire(Audit::class);
});

test('the show page mounts the tabs below the form', function (): void {
    $this->get(route('internal.controls.users.show', $this->subject))
        ->assertOk()
        ->assertSeeLivewire(Tabs::class);
});

test('the passkeys tab is hidden when passkeys are off', function (): void {
    $this->disableFeature(Feature::Passkeys);
    grantPermissions($this->actor, 'internal.controls.users.view-audit-log');

    Livewire::test(Tabs::class, ['user' => $this->subject])
        ->assertDontSeeLivewire(PasskeyDataTable::class)
        ->assertDontSee(__('user/users.tabs.passkeys'));
});

test('the audit tab shows with view-audit-log', function (): void {
    grantPermissions($this->actor, 'internal.controls.users.view-audit-log');

    Livewire::withQueryParams(['tab' => 'audit'])
        ->test(Tabs::class, ['user' => $this->subject])
        ->assertSeeLivewire(Audit::class);
});

test('the audit tab is hidden without view-audit-log', function (): void {
    Livewire::test(Tabs::class, ['user' => $this->subject])
        ->assertDontSee(__('user/users.tabs.audit'));
});
