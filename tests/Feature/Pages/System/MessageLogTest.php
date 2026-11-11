<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\MessageLogDataTable;
use App\Livewire\Pages\System\MessageLog;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->user = User::factory()->create();
    grantPermissions($this->user, 'system.access');
    $this->actingAs($this->user);
});

test('users without system.logs.messages.view cannot open the message log', function (): void {
    $response = $this->get(route('system.logs.messages'));

    $response->assertForbidden();
});

test('users with system.logs.messages.view can open the message log', function (): void {
    grantPermissions($this->user, 'system.logs.messages.view');

    $response = $this->get(route('system.logs.messages'));

    $response->assertOk()
        ->assertSeeLivewire(MessageLog::class)
        ->assertSeeLivewire(MessageLogDataTable::class)
        ->assertSee('<title>'.__('navigation.message_log').' - '.config('app.name').'</title>', false);
});
