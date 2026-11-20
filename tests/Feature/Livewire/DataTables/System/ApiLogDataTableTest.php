<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\ApiLogDataTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->viewer = User::factory()->create();
    grantPermissions($this->viewer, 'system.access', 'system.logs.api.view');
    $this->actingAs($this->viewer);
});

test('the list shows the calls of both directions, the newest first', function (): void {
    $inbound = ApiCall::factory()->inbound()->create();
    $outbound = ApiCall::factory()->outbound()->create();

    Livewire::test(ApiLogDataTable::class)->assertCanSeeTableRecords([$outbound, $inbound], inOrder: true);
});

test('the list names the API client of an inbound call, and the service of an outbound call with its linked API client', function (): void {
    $apiClient = ApiClient::factory()->create(['name' => 'Payroll']);
    $inbound = ApiCall::factory()->byClient($apiClient)->create();
    $outbound = ApiCall::factory()->outbound('billing')->byClient($apiClient)->create();
    $unlinked = ApiCall::factory()->outbound('maps')->create();

    Livewire::test(ApiLogDataTable::class)
        ->assertTableColumnStateSet('party', 'Payroll', $inbound)
        ->assertTableColumnStateSet('party', 'billing', $outbound)
        ->assertTableColumnHasDescription('party', 'Payroll', $outbound)
        ->assertTableColumnExists('party', fn (TextColumn $column): bool => $column->getDescriptionBelow() === null, $unlinked);
});

test('an empty cell says what is missing: no status for a failed connection, and no API client for a call without one', function (): void {
    $failed = ApiCall::factory()->connectionFailed()->create();
    $unknown = ApiCall::factory()->inbound()->rejected()->create(['api_client_id' => null, 'api_client_name' => null]);

    Livewire::test(ApiLogDataTable::class)
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getState() === null && $column->getPlaceholder() === __('api/api-calls.status_classes.none'), $failed)
        ->assertTableColumnExists('party', fn (TextColumn $column): bool => $column->getState() === null && $column->getPlaceholder() === __('api/api-calls.no_api_client'), $unknown);
});

test('the IP address column is hidden at first and can be shown with the column toggle', function (): void {
    $apiCall = ApiCall::factory()->inbound()->create(['ip_address' => '192.0.2.10']);

    Livewire::test(ApiLogDataTable::class)
        ->assertCanNotRenderTableColumn('ip_address')
        ->toggleAllTableColumns()
        ->assertCanRenderTableColumn('ip_address')
        ->assertTableColumnStateSet('ip_address', '192.0.2.10', $apiCall);
});

test('the IP address cell of an outbound call says that it has no IP address', function (): void {
    $outbound = ApiCall::factory()->outbound()->create();

    Livewire::test(ApiLogDataTable::class)
        ->toggleAllTableColumns()
        ->assertTableColumnExists('ip_address', fn (TextColumn $column): bool => $column->getState() === null && $column->getPlaceholder() === __('api/api-calls.no_ip_address'), $outbound)
        ->assertSee('No IP address');
});

test('the list shows the outcome as a label in the color of the outcome, and the duration in milliseconds', function (): void {
    $apiCall = ApiCall::factory()->rejected(ApiCallOutcome::RevokedToken)->create(['duration_ms' => 42]);

    Livewire::test(ApiLogDataTable::class)
        ->assertTableColumnFormattedStateSet('outcome', 'Revoked token', $apiCall)
        ->assertTableColumnExists('outcome', fn (TextColumn $column): bool => $column->getColor($column->getState()) === ApiCallOutcome::RevokedToken->getColor(), $apiCall)
        ->assertTableColumnFormattedStateSet('duration_ms', '42 ms', $apiCall);
});

test('the list filters the calls by direction, outcome, API client and service', function (): void {
    $apiClient = ApiClient::factory()->create();
    $revoked = ApiCall::factory()->byClient($apiClient)->rejected(ApiCallOutcome::RevokedToken)->create();
    $ok = ApiCall::factory()->create();
    $billing = ApiCall::factory()->outbound('billing')->create();
    $maps = ApiCall::factory()->outbound('maps')->create();

    Livewire::test(ApiLogDataTable::class)
        ->filterTable('direction', [ApiCallDirection::Outbound->value])
        ->assertCanSeeTableRecords([$billing, $maps])
        ->assertCanNotSeeTableRecords([$revoked, $ok])
        ->resetTableFilters()
        ->filterTable('outcome', [ApiCallOutcome::RevokedToken->value])
        ->assertCanSeeTableRecords([$revoked])
        ->assertCanNotSeeTableRecords([$ok, $billing, $maps])
        ->resetTableFilters()
        ->filterTable('api_client', [$apiClient->id])
        ->assertCanSeeTableRecords([$revoked])
        ->assertCanNotSeeTableRecords([$ok, $billing, $maps])
        ->resetTableFilters()
        ->filterTable('service', ['maps'])
        ->assertCanSeeTableRecords([$maps])
        ->assertCanNotSeeTableRecords([$revoked, $ok, $billing]);
});

test('the list filters the calls by status class, and a failed connection has no status', function (): void {
    $ok = ApiCall::factory()->create(['status' => 204]);
    $notFound = ApiCall::factory()->create(['status' => 404, 'outcome' => ApiCallOutcome::ClientError]);
    $serverError = ApiCall::factory()->serverError()->create();
    $failed = ApiCall::factory()->connectionFailed()->create();

    Livewire::test(ApiLogDataTable::class)
        ->filterTable('status', ['4xx', '5xx'])
        ->assertCanSeeTableRecords([$notFound, $serverError])
        ->assertCanNotSeeTableRecords([$ok, $failed])
        ->filterTable('status', ['none'])
        ->assertCanSeeTableRecords([$failed])
        ->assertCanNotSeeTableRecords([$ok, $notFound, $serverError]);
});

test('the list filters the calls by date', function (): void {
    $old = ApiCall::factory()->create(['created_at' => '2026-01-10 12:00:00']);
    $inside = ApiCall::factory()->create(['created_at' => '2026-02-10 12:00:00']);
    $new = ApiCall::factory()->create(['created_at' => '2026-03-10 12:00:00']);

    Livewire::test(ApiLogDataTable::class)
        ->filterTable('created_at', ['from' => '2026-02-01', 'until' => '2026-02-28'])
        ->assertCanSeeTableRecords([$inside])
        ->assertCanNotSeeTableRecords([$old, $new]);
});

test('a date bound that is not a Y-m-d date is ignored and shows no indicator', function (mixed $value): void {
    $call = ApiCall::factory()->create();

    Livewire::test(ApiLogDataTable::class)
        ->filterTable('created_at', ['from' => $value, 'until' => $value])
        ->assertOk()
        ->assertCanSeeTableRecords([$call])
        ->assertDontSee(['From: ', 'Until: ']);
})->with([
    'a word that Carbon reads' => ['tomorrow'],
    'a day that the month does not have' => ['2026-02-31'],
    'a line break after the date' => ["2026-02-01\n"],
    'an array' => [['2026-02-01']],
]);

test('a date filter shows its dates', function (): void {
    Livewire::test(ApiLogDataTable::class)
        ->filterTable('created_at', ['from' => '2026-02-01'])
        ->assertSee('From: ');
});

test('the API client filter with a malformed key keeps no calls and sends the key to no query', function (): void {
    ApiCall::factory()->byClient(ApiClient::factory()->create())->create();
    $table = Livewire::test(ApiLogDataTable::class);
    $bindings = collect();
    DB::listen(fn (QueryExecuted $query) => $bindings->push(...$query->bindings));

    $table->filterTable('api_client', ['abc'])
        ->assertOk()
        ->assertCountTableRecords(0);

    expect($bindings)->not->toContain('abc');
});

test('the status filter with no known status class keeps no calls', function (string $property, mixed $value): void {
    ApiCall::factory()->create(['status' => 204]);

    Livewire::test(ApiLogDataTable::class)
        ->set($property, $value)
        ->assertOk()
        ->assertCountTableRecords(0);
})->with([
    'an unknown class' => ['tableFilters.status.values', ['9xx']],
    'text for the state' => ['tableFilters.status', 'x'],
    'text for the list' => ['tableFilters.status.values', 'x'],
    'a nested list' => ['tableFilters.status.values', [[1]]],
]);

test('the status filter drops an empty value', function (string $property, array $values, int $count): void {
    ApiCall::factory()->create(['status' => 204]);
    ApiCall::factory()->create(['status' => 404, 'outcome' => ApiCallOutcome::ClientError]);

    Livewire::test(ApiLogDataTable::class)
        ->set($property, $values)
        ->call('applyTableFilters')
        ->assertOk()
        ->assertCountTableRecords($count);
})->with([
    'only null' => ['tableFilters.status.values', [null], 2],
    'a class and null' => ['tableFilters.status.values', ['2xx', null], 1],
    'a class and null in the filter form' => ['tableDeferredFilters.status.values', ['2xx', null], 1],
    'an empty string' => ['tableFilters.status.values', [''], 2],
]);

test('the search finds a call by its URL', function (): void {
    $orders = ApiCall::factory()->create(['url' => '/api/v1/orders']);
    $ping = ApiCall::factory()->create(['url' => '/api/v1/ping']);

    Livewire::test(ApiLogDataTable::class)
        ->searchTable('orders')
        ->assertCanSeeTableRecords([$orders])
        ->assertCanNotSeeTableRecords([$ping]);
});

test('the list of an API client shows only the calls of that API client', function (): void {
    $apiClient = ApiClient::factory()->create();
    $own = ApiCall::factory()->byClient($apiClient)->create();
    $linked = ApiCall::factory()->outbound()->byClient($apiClient)->create();
    $other = ApiCall::factory()->byClient(ApiClient::factory()->create())->create();

    Livewire::test(ApiLogDataTable::class, ['apiClient' => $apiClient])
        ->assertCanSeeTableRecords([$own, $linked])
        ->assertCanNotSeeTableRecords([$other]);
});

test('the list of an API client has no API client filter', function (): void {
    Livewire::test(ApiLogDataTable::class, ['apiClient' => ApiClient::factory()->create()])->assertTableFilterHidden('api_client');
});

test('the API log list needs system.logs.api.view', function (): void {
    revokePermissions($this->viewer, 'system.logs.api.view');

    Livewire::test(ApiLogDataTable::class)->assertForbidden();
});
