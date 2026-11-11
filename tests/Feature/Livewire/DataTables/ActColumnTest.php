<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\DataTables\InvoiceDataTable;
use Tests\Fixtures\Models\Invoice;

beforeEach(function (): void {
    Schema::create('invoices', function (Blueprint $table): void {
        $table->id();
        $table->string('number');
        $table->timestampsTz();
        $table->softDeletesTz();
    });

    Relation::morphMap(['invoice' => Invoice::class]);
});

function prepareInvoice(User $user, string $number = 'INV-1'): Invoice
{
    test()->actingAs($user);

    return Invoice::query()->create(['number' => $number]);
}

function profileWithPosition(string $name, string $position): Profile
{
    $position = Position::factory()->create(['name' => $position]);

    return Profile::factory()->for($position->organization)->for($position)->for(User::factory()->state(['name' => $name]))->create();
}

test('an act column shows who acted, their position at that time, and when', function (): void {
    $invoice = prepareInvoice(profileWithPosition('Ada Lovelace', 'Clerk')->user);

    Livewire::test(InvoiceDataTable::class)
        ->assertSeeInOrder(['Ada Lovelace', 'Clerk', (string) Preferences::current()->datetime($invoice->preparer?->acted_at)]);
});

test('an act column shows the name of a user without a profile', function (): void {
    prepareInvoice(User::factory()->create(['name' => 'Ada Lovelace']));

    Livewire::test(InvoiceDataTable::class)->assertSeeText('Ada Lovelace');
});

test('an act column shows the name of a user who was deleted', function (): void {
    $profile = profileWithPosition('Ada Lovelace', 'Clerk');
    prepareInvoice($profile->user);
    $profile->user->delete();

    Livewire::test(InvoiceDataTable::class)->assertSeeText('Ada Lovelace');
});

test('an act column leaves the cell empty when the record has no such action', function (): void {
    Invoice::query()->create(['number' => 'INV-1']);

    Livewire::test(InvoiceDataTable::class)->assertSeeText('INV-1');
});

test('an act column loads its relations once for the whole page', function (): void {
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(InvoiceDataTable::class);

        return count(DB::getQueryLog());
    };

    prepareInvoice(profileWithPosition('Ada Lovelace', 'Clerk')->user, 'INV-1');
    $queriesForOneRow = $countQueries();

    prepareInvoice(profileWithPosition('Grace Hopper', 'Manager')->user, 'INV-2');
    prepareInvoice(User::factory()->create(), 'INV-3');
    prepareInvoice(User::factory()->create(), 'INV-4');

    expect($countQueries())->toBe($queriesForOneRow);
});

test('an act column loads a deleted position without extra queries per row', function (): void {
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(InvoiceDataTable::class);

        return count(DB::getQueryLog());
    };

    $profile = profileWithPosition('Ada Lovelace', 'Clerk');
    prepareInvoice($profile->user, 'INV-1');
    $profile->position?->delete();
    $queriesForOneRow = $countQueries();

    foreach (range(2, 10) as $number) {
        prepareInvoice(profileWithPosition("User {$number}", "Position {$number}")->user, "INV-{$number}");
    }

    Position::query()->delete();

    Livewire::test(InvoiceDataTable::class)->assertSeeText('Clerk');
    expect($countQueries())->toBe($queriesForOneRow);
});
