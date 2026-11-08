<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Organization\UserDataTable;
use App\Livewire\Pages\Internal\Controls\Organization\Audit;
use App\Livewire\Pages\Internal\Controls\Organization\Branch\Tabs;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view');
    $this->actingAs($this->actor);
    $this->branch = Branch::factory()->create();
});

test('the branch users tab shows with view-any on users', function (): void {
    Livewire::test(Tabs::class, ['branch' => $this->branch])
        ->assertDontSeeLivewire(UserDataTable::class);

    grantPermissions($this->actor, 'internal.controls.users.view-any');

    Livewire::withQueryParams(['tab' => 'users'])
        ->test(Tabs::class, ['branch' => $this->branch])
        ->assertSeeLivewire(UserDataTable::class);
});

test('the branch audit tab shows with view-audit-log', function (): void {
    Livewire::test(Tabs::class, ['branch' => $this->branch])
        ->assertDontSeeLivewire(Audit::class);

    grantPermissions($this->actor, 'internal.controls.organizations.view-audit-log');

    Livewire::withQueryParams(['tab' => 'audit'])
        ->test(Tabs::class, ['branch' => $this->branch])
        ->assertSeeLivewire(Audit::class);
});
