<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\ApiLogDataTable;
use App\Livewire\Pages\System\ApiLog;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->user = User::factory()->create();
    grantPermissions($this->user, 'system.access');
    $this->actingAs($this->user);
});

test('users without system.logs.api.view cannot open the API log', function (): void {
    $response = $this->get(route('system.logs.api'));

    $response->assertForbidden();
});

test('users with system.logs.api.view can open the API log', function (): void {
    grantPermissions($this->user, 'system.logs.api.view');

    $response = $this->get(route('system.logs.api'));

    $response->assertOk()
        ->assertSeeLivewire(ApiLog::class)
        ->assertSeeLivewire(ApiLogDataTable::class)
        ->assertSee('<title>'.__('navigation.api_log').' - '.config('app.name').'</title>', false);
});
