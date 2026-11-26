<?php

declare(strict_types=1);

use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Settings\SignInLogDataTable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('the table lists the sign-ins and failed sign-ins of the user in the last 30 days', function (): void {
    $signIn = SignInEntry::factory()->forUser($this->user)->create();
    $failed = SignInEntry::factory()->failed()->forUser($this->user)->create(['created_at' => now()->subDays(29)]);

    Livewire::test(SignInLogDataTable::class)->assertCanSeeTableRecords([$signIn, $failed]);
});

test('the table never shows an entry of another user, an entry older than 30 days, or a sign-out or impersonation entry', function (): void {
    $otherUser = SignInEntry::factory()->create();
    $old = SignInEntry::factory()->forUser($this->user)->create(['created_at' => now()->subDays(31)]);
    $signOut = SignInEntry::factory()->signOut()->forUser($this->user)->create();
    $started = SignInEntry::factory()->impersonationStarted()->forUser($this->user)->create();
    $stopped = SignInEntry::factory()->impersonationStopped()->forUser($this->user)->create();
    $asImpersonator = SignInEntry::factory()->impersonationStarted()->create(['impersonator_id' => $this->user->id]);

    Livewire::test(SignInLogDataTable::class)
        ->assertCountTableRecords(0)
        ->assertCanNotSeeTableRecords([$otherUser, $old, $signOut, $started, $stopped, $asImpersonator]);
});

test('the table shows the time, result, method, IP address and user agent of each entry', function (): void {
    SignInEntry::factory()->forUser($this->user)->create();

    Livewire::test(SignInLogDataTable::class)
        ->assertCanRenderTableColumn('created_at')
        ->assertCanRenderTableColumn('result')
        ->assertCanRenderTableColumn('method')
        ->assertCanRenderTableColumn('ip_address')
        ->assertCanRenderTableColumn('user_agent')
        ->assertTableColumnDoesNotExist('user.name')
        ->assertTableColumnDoesNotExist('impersonator.name')
        ->assertTableColumnDoesNotExist('trace_id');
});

test('the table loads no user or impersonator, because it shows neither', function (): void {
    SignInEntry::factory()->impersonationStarted()->forUser($this->user)->create();
    SignInEntry::factory()->forUser($this->user)->create();
    $users = new User()->getTable();
    $queries = collect();
    DB::listen(fn (QueryExecuted $query) => $queries->push($query->sql));

    Livewire::test(SignInLogDataTable::class)->assertCountTableRecords(1);

    expect($queries)->not->toBeEmpty()
        ->and($queries->filter(fn (string $sql): bool => str($sql)->isMatch("/from\\W+$users\\W/i")))->toBeEmpty();
});
