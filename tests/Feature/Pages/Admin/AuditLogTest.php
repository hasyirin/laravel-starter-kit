<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\AuditLogDataTable;
use App\Livewire\Pages\Admin\AuditLog;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->user = User::factory()->create();
    grantPermissions($this->user, 'system.access');
    $this->actingAs($this->user);
});

test('users without system.audit-log.view cannot open the audit log', function (): void {
    $response = $this->get(route('admin.audit-log'));

    $response->assertForbidden();
});

test('users with system.audit-log.view can open the audit log', function (): void {
    grantPermissions($this->user, 'system.audit-log.view');

    $response = $this->get(route('admin.audit-log'));

    $response->assertOk()
        ->assertSeeLivewire(AuditLog::class)
        ->assertSeeLivewire(AuditLogDataTable::class)
        ->assertSee('<title>'.__('navigation.audit_log').' - '.config('app.name').'</title>', false);
});
