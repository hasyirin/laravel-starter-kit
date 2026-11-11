<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use App\Livewire\DataTables\System\MessageLogDataTable;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
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

test('the list shows the channel of each message and filters them by channel', function (): void {
    $email = Message::factory()->create();
    $sms = Message::factory()->sms()->create();
    $pager = Message::factory()->create(['channel' => 'App\\Channels\\PagerChannel']);

    Livewire::test(MessageLogDataTable::class)
        ->assertTableColumnFormattedStateSet('channel', 'Email', $email)
        ->assertTableColumnFormattedStateSet('channel', 'SMS', $sms)
        ->assertTableColumnFormattedStateSet('channel', 'Pager', $pager)
        ->filterTable('channel', ['sms'])
        ->assertCanSeeTableRecords([$sms])
        ->assertCanNotSeeTableRecords([$email, $pager]);
});

test('the content column is hidden at first, and it shows the subject of an email and the text of an sms', function (): void {
    $email = Message::factory()->create(['subject' => 'Letter issued']);
    $unnamed = Message::factory()->create(['subject' => null, 'text_body' => 'The body of an email.']);
    $sms = Message::factory()->sms()->create(['text_body' => 'Your letter is ready.']);

    Livewire::test(MessageLogDataTable::class)
        ->assertCanNotRenderTableColumn('content')
        ->toggleAllTableColumns()
        ->assertCanRenderTableColumn('content')
        ->assertTableColumnStateSet('content', 'Letter issued', $email)
        ->assertTableColumnExists('content', fn (TextColumn $column): bool => $column->getState() === null, $unnamed)
        ->assertTableColumnStateSet('content', 'Your letter is ready.', $sms);
});

test('the search finds an email by its subject and an sms by its text, and it does not read the body of an email', function (): void {
    $email = Message::factory()->create(['subject' => 'Letter issued', 'text_body' => 'Your permit is ready.']);
    $sms = Message::factory()->sms()->create(['text_body' => 'Your letter is ready.']);

    Livewire::test(MessageLogDataTable::class)
        ->searchTable('letter issued')
        ->assertCanSeeTableRecords([$email])
        ->assertCanNotSeeTableRecords([$sms])
        ->searchTable('is ready')
        ->assertCanSeeTableRecords([$sms])
        ->assertCanNotSeeTableRecords([$email]);
});

test('the list shows the signs of an open, a click, and a view only for a tracked message', function (): void {
    $tracked = Message::factory()->tracked()->create(['opened_at' => now()]);
    $untracked = Message::factory()->create();
    $sms = Message::factory()->sms()->create();
    $unknown = fn (IconColumn $column): bool => $column->getState() === null;

    Livewire::test(MessageLogDataTable::class)
        ->assertTableColumnExists('opened', fn (IconColumn $column): bool => $column->getState() === true, $tracked)
        ->assertTableColumnExists('clicked', fn (IconColumn $column): bool => $column->getState() === false, $tracked)
        ->assertTableColumnExists('viewed', fn (IconColumn $column): bool => $column->getState() === false, $tracked)
        ->assertTableColumnExists('opened', $unknown, $untracked)
        ->assertTableColumnExists('opened', $unknown, $sms)
        ->assertTableColumnExists('clicked', $unknown, $sms)
        ->assertTableColumnExists('viewed', $unknown, $sms);
});

test('the list shows when the app started the send and when the message was sent, with the date above the time', function (): void {
    $this->freezeSecond();
    $sent = Message::factory()->create(['created_at' => now()->subMinutes(6)]);
    $failed = Message::factory()->failed()->create();
    $preferences = Preferences::current();

    Livewire::test(MessageLogDataTable::class)
        ->assertTableColumnFormattedStateSet('created_at', $preferences->date(now()->subMinutes(6)), $sent)
        ->assertTableColumnHasDescription('created_at', $preferences->time(now()->subMinutes(6)), $sent)
        ->assertTableColumnFormattedStateSet('sent_at', $preferences->date(now()), $sent)
        ->assertTableColumnHasDescription('sent_at', $preferences->time(now()), $sent)
        ->assertTableColumnStateSet('sent_at', null, $failed);
});

test('the list marks a resend, and the message that staff resent', function (): void {
    $first = Message::factory()->failed()->create();
    $second = Message::factory()->failed()->create(['resend_of_id' => $first->id]);
    $third = Message::factory()->create(['resend_of_id' => $second->id]);
    Message::factory()->create(['resend_of_id' => $first->id]);
    $other = Message::factory()->create();

    Livewire::test(MessageLogDataTable::class)
        ->assertTableColumnStateSet('resend', ['resent'], $first)
        ->assertTableColumnStateSet('resend', ['resend', 'resent'], $second)
        ->assertTableColumnStateSet('resend', ['resend'], $third)
        ->assertTableColumnStateSet('resend', null, $other)
        ->assertSee('Resent 2 times');
});
