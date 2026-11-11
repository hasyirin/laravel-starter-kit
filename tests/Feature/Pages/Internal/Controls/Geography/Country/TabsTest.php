<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Geography\Audit;
use App\Livewire\Pages\Internal\Controls\Geography\Country\Tabs;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view');
    $this->actingAs($this->actor);
    $this->country = Country::factory()->create();
});

test('the country audit tab shows with view-audit-log', function (): void {
    Livewire::test(Tabs::class, ['country' => $this->country])
        ->assertDontSee(__('geography/countries.tabs.audit'));

    grantPermissions($this->actor, 'internal.controls.geography.view-audit-log');

    Livewire::withQueryParams(['tab' => 'audit'])
        ->test(Tabs::class, ['country' => $this->country])
        ->assertSeeLivewire(Audit::class);
});
