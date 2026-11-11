<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Geography\Audit;
use App\Livewire\Pages\Internal\Controls\Geography\PostOffice\Tabs;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view');
    $this->actingAs($this->actor);
    $this->postOffice = PostOffice::factory()->create();
});

test('the city audit tab shows with view-audit-log', function (): void {
    Livewire::test(Tabs::class, ['postOffice' => $this->postOffice])
        ->assertDontSee(__('geography/cities.tabs.audit'));

    grantPermissions($this->actor, 'internal.controls.geography.view-audit-log');

    Livewire::withQueryParams(['tab' => 'audit'])
        ->test(Tabs::class, ['postOffice' => $this->postOffice])
        ->assertSeeLivewire(Audit::class);
});
