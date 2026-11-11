<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Enums\MailStatus;
use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\MailLogDataTable;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->viewer = User::factory()->create();
    grantPermissions($this->viewer, 'system.access', 'system.logs.mail.view');
    $this->actingAs($this->viewer);
});

test('the list shows every email and filters them by status', function (): void {
    $sent = Mail::factory()->create();
    $failed = Mail::factory()->failed()->create();

    Livewire::test(MailLogDataTable::class)
        ->assertCanSeeTableRecords([$sent, $failed])
        ->filterTable('status', [MailStatus::Failed->value])
        ->assertCanSeeTableRecords([$failed])
        ->assertCanNotSeeTableRecords([$sent]);
});

test('the list shows an email without a recipient', function (): void {
    $mail = Mail::factory()->create(['recipient_email' => null]);

    Livewire::test(MailLogDataTable::class)->assertCanSeeTableRecords([$mail]);
});

test('the email lists need system.logs.mail.view', function (): void {
    revokePermissions($this->viewer, 'system.logs.mail.view');

    Livewire::test(MailLogDataTable::class)->assertForbidden();
});
