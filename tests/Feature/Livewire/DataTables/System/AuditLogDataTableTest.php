<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Support\AuditLog;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Enums\AuditDirection;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\User\AuditLogDataTable as UserAuditLogDataTable;
use App\Livewire\DataTables\System\AuditLogDataTable;
use App\Livewire\Pages\System\AuditEntryModal;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\ExpectationFailedException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);

    $this->viewer = User::factory()->create(['name' => 'Vera Viewer']);
    grantPermissions($this->viewer, 'system.access', 'system.logs.audits.view');

    $this->ada = User::factory()->create(['name' => 'Ada Lovelace']);
    $this->sam = User::factory()->create(['name' => 'Sam Subject']);
});

function changeAs(?User $causer, Closure $change): Activity
{
    auth()->logout();
    context()->forget('user_id');

    if ($causer instanceof User) {
        test()->actingAs($causer);
    }

    $change();

    return Activity::query()->latest('id')->firstOrFail();
}

test('the list shows changes to every kind of record by every user', function (): void {
    $onUser = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Renamed']));
    $onRole = changeAs($this->sam, fn () => Role::query()->create(['name' => 'editor']));
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->assertCanSeeTableRecords([$onUser, $onRole])
        ->assertSee(['Ada Lovelace', 'Sam Renamed']);
});

test('the audit log needs system.logs.audits.view', function (string $component): void {
    revokePermissions($this->viewer, 'system.logs.audits.view');
    $this->actingAs($this->viewer);

    Livewire::test($component)->assertForbidden();
})->with([AuditLogDataTable::class, AuditEntryModal::class]);

test('the causer filter keeps the changes by the picked users', function (): void {
    $byAda = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Renamed']));
    $bySam = changeAs($this->sam, fn () => $this->ada->update(['name' => 'Ada Renamed']));
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->filterTable('causer', ['type' => 'user', 'ids' => [(string) $this->ada->id]])
        ->assertCanSeeTableRecords([$byAda])
        ->assertCanNotSeeTableRecords([$bySam]);
});

test('the causer filter with a type and no picked users keeps every change by that type', function (): void {
    $byAda = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Renamed']));
    $byNobody = changeAs(null, fn () => $this->sam->update(['name' => 'Sam Again']));
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->filterTable('causer', ['type' => 'user'])
        ->assertCanSeeTableRecords([$byAda])
        ->assertCanNotSeeTableRecords([$byNobody]);
});

test('the list shows the name of a deleted causer', function (): void {
    $entry = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Renamed']));
    $this->ada->delete();
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->assertTableColumnStateSet('causer_name', 'Ada Lovelace', $entry);
});

test('each active filter shows what it keeps', function (string $filter, array $data, string $indicator): void {
    $this->actingAs($this->viewer);
    $data = collect($data)->map(fn (mixed $value): mixed => $value === 'ada' ? [(string) $this->ada->id] : $value)->all();

    Livewire::test(AuditLogDataTable::class)
        ->filterTable($filter, $data)
        ->assertSee($indicator);
})->with([
    'a causer type' => ['causer', ['type' => AuditLog::NO_CAUSER], 'By: None'],
    'picked causers' => ['causer', ['type' => 'user', 'ids' => 'ada'], 'By: Ada Lovelace'],
    'a subject type' => ['subject', ['type' => 'role'], 'Subject: Role'],
    'picked impersonators' => ['impersonator', ['ids' => 'ada'], 'Impersonated by: Ada Lovelace'],
    'only impersonated changes' => ['impersonator', ['only' => true], 'Only impersonated changes'],
    'a time range' => ['created_at', ['from' => '2026-01-02'], 'From: '],
]);

test('a causer, subject or impersonator filter with only malformed ids keeps no changes and sends no id to a query', function (string $filter, array $data): void {
    changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Renamed']));
    $this->actingAs($this->viewer);
    $table = Livewire::test(AuditLogDataTable::class);
    $bindings = collect();
    DB::listen(fn (QueryExecuted $query) => $bindings->push(...$query->bindings));

    $table->filterTable($filter, $data)
        ->assertOk()
        ->assertCountTableRecords(0);

    expect($bindings)->not->toContain('abc');
})->with([
    'causer' => ['causer', ['type' => 'user', 'ids' => ['abc']]],
    'subject' => ['subject', ['type' => 'user', 'ids' => ['abc']]],
    'impersonator' => ['impersonator', ['ids' => ['abc']]],
    'impersonator with only impersonated changes' => ['impersonator', ['only' => true, 'ids' => ['abc']]],
]);

test('a causer filter keeps the changes by its valid ids and drops the malformed ones', function (): void {
    $byAda = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Renamed']));
    $bySam = changeAs($this->sam, fn () => $this->ada->update(['name' => 'Ada Renamed']));
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->filterTable('causer', ['type' => 'user', 'ids' => [(string) $this->ada->id, 'abc']])
        ->assertCanSeeTableRecords([$byAda])
        ->assertCanNotSeeTableRecords([$bySam])
        ->assertSee('By: Ada Renamed');
});

test('a date bound that is not a Y-m-d date is ignored and shows no indicator', function (mixed $value): void {
    $entry = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Renamed']));
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->filterTable('created_at', ['from' => $value, 'until' => $value])
        ->assertOk()
        ->assertCanSeeTableRecords([$entry])
        ->assertDontSee(['From: ', 'Until: ']);
})->with([
    'a word that Carbon reads' => ['tomorrow'],
    'letters' => ['abc'],
    'a five digit year' => ['99999-01-01'],
    'a day that the month does not have' => ['2026-02-31'],
    'a line break after the date' => ["2026-02-01\n"],
    'no leading zeros' => ['2026-2-1'],
    'the year zero' => ['0000-01-01'],
    'an array' => [['2026-02-01']],
]);

test('a time filter of the wrong shape is ignored', function (): void {
    $entry = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Renamed']));
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->set('tableFilters.created_at', 'x')
        ->assertOk()
        ->assertCanSeeTableRecords([$entry]);
});

test('the event filter drops an empty value', function (): void {
    $entry = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Renamed']));
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->set('tableFilters.event.values', [null])
        ->assertOk()
        ->assertCanSeeTableRecords([$entry]);
});

test('the date pickers of the time filter keep a Y-m-d date', function (): void {
    $this->actingAs($this->viewer);
    $table = Livewire::test(AuditLogDataTable::class)->instance();

    $table->getTableFiltersForm()->fill(['created_at' => ['from' => CarbonImmutable::parse('2026-02-01 13:45')]]);

    expect($table->tableDeferredFilters['created_at']['from'] ?? null)->toBe('2026-02-01');
});

test('the causer filter with none keeps the changes that no user made', function (): void {
    $byAda = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Renamed']));
    $byNobody = changeAs(null, fn () => $this->sam->update(['name' => 'Sam Again']));
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->filterTable('causer', ['type' => AuditLog::NO_CAUSER])
        ->assertCanSeeTableRecords([$byNobody])
        ->assertCanNotSeeTableRecords([$byAda]);
});

test('the subject filter keeps the picked records, or every record of the type when none is picked', function (): void {
    $onSam = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Renamed']));
    $onAda = changeAs($this->ada, fn () => $this->ada->update(['name' => 'Ada Renamed']));
    $onRole = changeAs($this->ada, fn () => Role::query()->create(['name' => 'editor']));
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->filterTable('subject', ['type' => 'user', 'ids' => [(string) $this->sam->id]])
        ->assertCanSeeTableRecords([$onSam])
        ->assertCanNotSeeTableRecords([$onAda, $onRole])
        ->filterTable('subject', ['type' => 'user'])
        ->assertCanSeeTableRecords([$onSam, $onAda])
        ->assertCanNotSeeTableRecords([$onRole]);
});

test('the impersonator filter keeps the changes made while the picked users impersonated', function (): void {
    $ina = User::factory()->create(['name' => 'Ina Impersonator']);
    $ivan = User::factory()->create(['name' => 'Ivan Impersonator']);
    $plain = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Plain']));
    context()->add('impersonator_id', $ina->id);
    $byIna = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam By Ina']));
    context()->add('impersonator_id', $ivan->id);
    $byIvan = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam By Ivan']));
    context()->forget('impersonator_id');
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->filterTable('impersonator', ['ids' => [(string) $ina->id]])
        ->assertCanSeeTableRecords([$byIna])
        ->assertCanNotSeeTableRecords([$plain, $byIvan])
        ->filterTable('impersonator', ['only' => true])
        ->assertCanSeeTableRecords([$byIna, $byIvan])
        ->assertCanNotSeeTableRecords([$plain]);
});

test('the impersonated by column shows the impersonator of an entry', function (): void {
    $ina = User::factory()->create(['name' => 'Ina Impersonator']);
    context()->add('impersonator_id', $ina->id);
    $entry = changeAs($this->ada, fn () => $this->sam->update(['name' => 'Sam Renamed']));
    context()->forget('impersonator_id');
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->assertTableColumnStateSet('impersonator_name', 'Ina Impersonator', $entry);
});

test('the impersonator filter is only on the system audit log', function (): void {
    grantPermissions($this->viewer, 'internal.access', 'internal.controls.users.view', 'internal.controls.users.view-audit-log');
    $this->actingAs($this->viewer);

    $userTable = Livewire::test(UserAuditLogDataTable::class, ['user' => $this->sam, 'direction' => AuditDirection::On]);

    $userTable->assertTableFilterExists('causer')->assertTableFilterExists('subject');
    expect(fn () => $userTable->assertTableFilterExists('impersonator'))->toThrow(ExpectationFailedException::class);
});

test('a row click opens the modal, which shows an entry on any record', function (): void {
    Profile::factory()->for($this->sam)->create();
    $entry = Activity::query()->latest('id')->firstOrFail();
    $this->actingAs($this->viewer);

    Livewire::test(AuditLogDataTable::class)
        ->callAction(TestAction::make('view')->table($entry))
        ->assertDispatchedTo(AuditEntryModal::class, 'show', id: $entry->id);

    Livewire::test(AuditEntryModal::class)
        ->call('show', $entry->id)
        ->assertSet('showing', true)
        ->assertSeeInOrder(['User', 'Sam Subject']);
});

test('the modal shows the letter data, extra values, print details and images of a printed manual letter', function (): void {
    Storage::fake('media');
    $entry = activity()->event('printed-manual-letter')->withProperties([
        'type' => 'notice',
        'data' => ['title' => 'Annual <b>Notice</b>', 'amount' => 42],
        'extra' => ['remark' => 'Handle with care'],
        'details' => ['copies' => 3],
        'source_type' => 'user',
        'source_id' => $this->sam->id,
    ])->log('printed');
    $entry->addMedia(UploadedFile::fake()->image('stamp.png'))->toMediaCollection();
    $this->actingAs($this->viewer);

    Livewire::test(AuditEntryModal::class)
        ->call('show', $entry->id)
        ->assertSeeInOrder(['notice', 'Title', 'Annual', 'Amount', '42', 'Remark', 'Handle with care', 'Copies', '3'])
        ->assertSeeHtml('Annual &lt;b&gt;Notice&lt;/b&gt;')
        ->assertSee('stamp.png')
        ->assertSeeHtml(route('media.show', $entry->getFirstMedia()));
});
