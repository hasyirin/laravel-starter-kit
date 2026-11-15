<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\ApiClientDataTable;
use App\Livewire\Pages\System\ApiClient\Index;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'system.access');
    $this->actingAs($this->actor);
});

test('the API client list needs view-any', function (): void {
    $this->get(route('system.api-clients.index'))->assertForbidden();

    grantPermissions($this->actor, 'system.api-clients.view-any');

    $this->get(route('system.api-clients.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSeeLivewire(ApiClientDataTable::class)
        ->assertSee('<title>'.__('api/api-clients.title').' - '.config('app.name').'</title>', false);
});

test('the navigation shows the API clients item only with view-any', function (): void {
    $this->get(route('system.dashboard'))->assertDontSee(route('system.api-clients.index'));

    grantPermissions($this->actor, 'system.api-clients.view-any');

    $this->get(route('system.dashboard'))->assertSee(route('system.api-clients.index'));
});
