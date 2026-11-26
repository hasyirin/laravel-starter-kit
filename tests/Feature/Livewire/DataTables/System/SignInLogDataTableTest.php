<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\SignInLogDataTable;
use Filament\Tables\Columns\TextColumn;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->viewer = User::factory()->create();
    grantPermissions($this->viewer, 'system.access', 'system.logs.sign-ins.view');
    $this->actingAs($this->viewer);
});

test('the list shows the entries of every user, the newest first', function (): void {
    $first = SignInEntry::factory()->create();
    $second = SignInEntry::factory()->failed()->create();
    $third = SignInEntry::factory()->signOut()->create();

    Livewire::test(SignInLogDataTable::class)->assertCanSeeTableRecords([$third, $second, $first], inOrder: true);
});

test('the list names the user and the impersonator, and says when no user matched', function (): void {
    $impersonation = SignInEntry::factory()->impersonationStarted()->create();
    $unknown = SignInEntry::factory()->failed(SignInResult::NoMatchingUser)->create();

    Livewire::test(SignInLogDataTable::class)
        ->assertTableColumnStateSet('user.name', $impersonation->user?->name, $impersonation)
        ->assertTableColumnStateSet('impersonator.name', $impersonation->impersonator?->name, $impersonation)
        ->assertTableColumnExists('user.name', fn (TextColumn $column): bool => $column->getState() === null && $column->getPlaceholder() === __('user/sign-in-log.no_user'), $unknown);
});

test('the list shows the result as a label in the color of the result', function (): void {
    $entry = SignInEntry::factory()->failed(SignInResult::WrongTwoFactorCode)->create();

    Livewire::test(SignInLogDataTable::class)
        ->assertTableColumnFormattedStateSet('result', 'Wrong two-factor code', $entry)
        ->assertTableColumnExists('result', fn (TextColumn $column): bool => $column->getColor($column->getState()) === SignInResult::WrongTwoFactorCode->getColor(), $entry);
});

test('the user agent and trace ID columns are hidden at first and can be shown with the column toggle', function (): void {
    $entry = SignInEntry::factory()->create(['trace_id' => 'trace-1']);

    Livewire::test(SignInLogDataTable::class)
        ->assertCanNotRenderTableColumn('user_agent')
        ->assertCanNotRenderTableColumn('trace_id')
        ->toggleAllTableColumns()
        ->assertCanRenderTableColumn('user_agent')
        ->assertTableColumnStateSet('trace_id', 'trace-1', $entry);
});

test('the user filter finds the entries of the user and the entries the user made as an impersonator', function (): void {
    $user = User::factory()->create();
    $own = SignInEntry::factory()->forUser($user)->create();
    $asImpersonator = SignInEntry::factory()->impersonationStarted()->create(['impersonator_id' => $user->id]);
    $other = SignInEntry::factory()->create();

    Livewire::test(SignInLogDataTable::class)
        ->filterTable('user', [$user->id])
        ->assertCanSeeTableRecords([$own, $asImpersonator])
        ->assertCanNotSeeTableRecords([$other]);
});

test('the user filter with a malformed key keeps no entries', function (): void {
    SignInEntry::factory()->create();

    Livewire::test(SignInLogDataTable::class)
        ->filterTable('user', ['abc'])
        ->assertOk()
        ->assertCountTableRecords(0);
});

test('the result filter keeps the entries with the chosen results', function (): void {
    $signedIn = SignInEntry::factory()->create();
    $lockedOut = SignInEntry::factory()->failed(SignInResult::LockedOut)->create();

    Livewire::test(SignInLogDataTable::class)
        ->filterTable('result', [SignInResult::LockedOut->value])
        ->assertCanSeeTableRecords([$lockedOut])
        ->assertCanNotSeeTableRecords([$signedIn]);
});

test('the IP address filter keeps only the entries from that exact address', function (): void {
    $match = SignInEntry::factory()->create(['ip_address' => '192.0.2.1']);
    $prefix = SignInEntry::factory()->create(['ip_address' => '192.0.2.10']);

    Livewire::test(SignInLogDataTable::class)
        ->filterTable('ip_address', ['ip_address' => '192.0.2.1'])
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$prefix])
        ->assertSee('IP address: 192.0.2.1');
});

test('an IP address filter that is not text is ignored', function (): void {
    $entry = SignInEntry::factory()->create();

    Livewire::test(SignInLogDataTable::class)
        ->filterTable('ip_address', ['ip_address' => ['192.0.2.1']])
        ->assertOk()
        ->assertCanSeeTableRecords([$entry]);
});

test('the date filter keeps the entries between its dates', function (): void {
    $old = SignInEntry::factory()->create(['created_at' => '2026-01-10 12:00:00']);
    $inside = SignInEntry::factory()->create(['created_at' => '2026-02-10 12:00:00']);
    $new = SignInEntry::factory()->create(['created_at' => '2026-03-10 12:00:00']);

    Livewire::test(SignInLogDataTable::class)
        ->filterTable('created_at', ['from' => '2026-02-01', 'until' => '2026-02-28'])
        ->assertCanSeeTableRecords([$inside])
        ->assertCanNotSeeTableRecords([$old, $new])
        ->assertSee('From: ');
});

test('the sign-in log list needs system.logs.sign-ins.view', function (): void {
    revokePermissions($this->viewer, 'system.logs.sign-ins.view');

    Livewire::test(SignInLogDataTable::class)->assertForbidden();
});
