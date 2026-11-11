<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\MailLogDataTable;
use App\Livewire\Pages\System\MailLog;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->user = User::factory()->create();
    grantPermissions($this->user, 'system.access');
    $this->actingAs($this->user);
});

test('users without system.logs.mail.view cannot open the mail log', function (): void {
    $response = $this->get(route('system.logs.mail'));

    $response->assertForbidden();
});

test('users with system.logs.mail.view can open the mail log', function (): void {
    grantPermissions($this->user, 'system.logs.mail.view');

    $response = $this->get(route('system.logs.mail'));

    $response->assertOk()
        ->assertSeeLivewire(MailLog::class)
        ->assertSeeLivewire(MailLogDataTable::class)
        ->assertSee('<title>'.__('navigation.mail_log').' - '.config('app.name').'</title>', false);
});
