<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\SignInLogDataTable;
use App\Livewire\Pages\System\SignInLog;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->user = User::factory()->create();
    grantPermissions($this->user, 'system.access');
    $this->actingAs($this->user);
});

test('users without system.logs.sign-ins.view cannot open the sign-in log', function (): void {
    $response = $this->get(route('system.logs.sign-ins'));

    $response->assertForbidden();
});

test('users with system.logs.sign-ins.view can open the sign-in log', function (): void {
    grantPermissions($this->user, 'system.logs.sign-ins.view');

    $response = $this->get(route('system.logs.sign-ins'));

    $response->assertOk()
        ->assertSeeLivewire(SignInLog::class)
        ->assertSeeLivewire(SignInLogDataTable::class)
        ->assertSee('<title>'.__('navigation.sign_in_log').' - '.config('app.name').'</title>', false);
});
