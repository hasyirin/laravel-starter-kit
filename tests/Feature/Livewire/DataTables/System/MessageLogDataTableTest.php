<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\MessageLogDataTable;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->viewer = User::factory()->create();
    grantPermissions($this->viewer, 'system.access', 'system.logs.messages.view');
    $this->actingAs($this->viewer);
});

test('the list shows every email and filters them by status', function (): void {
    $sent = Message::factory()->create();
    $failed = Message::factory()->failed()->create();

    Livewire::test(MessageLogDataTable::class)
        ->assertCanSeeTableRecords([$sent, $failed])
        ->filterTable('status', [MessageStatus::Failed->value])
        ->assertCanSeeTableRecords([$failed])
        ->assertCanNotSeeTableRecords([$sent]);
});

test('the list shows an email without a recipient', function (): void {
    $message = Message::factory()->create(['recipient_address' => null]);

    Livewire::test(MessageLogDataTable::class)->assertCanSeeTableRecords([$message]);
});

test('the email lists need system.logs.messages.view', function (): void {
    revokePermissions($this->viewer, 'system.logs.messages.view');

    Livewire::test(MessageLogDataTable::class)->assertForbidden();
});
