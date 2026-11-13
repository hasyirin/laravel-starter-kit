<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\ApiClientDataTable;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'system.access', 'system.api-clients.view-any', 'system.api-clients.create', 'system.api-clients.delete');
    $this->actingAs($this->actor);
    $this->apiClient = ApiClient::factory()->create(['name' => 'Billing']);
});

test('the list shows each API client with its active API tokens, its last use, and whether any IP address is allowed', function (): void {
    $this->freezeSecond();
    ApiToken::factory()->for($this->apiClient)->create(['last_used_at' => now()->subDay()]);
    ApiToken::factory()->for($this->apiClient)->create(['last_used_at' => now()->subHour()]);
    ApiToken::factory()->for($this->apiClient)->revoked()->create();
    $open = ApiClient::factory()->anyIpAddress()->disabled()->create(['service' => 'billing']);

    Livewire::test(ApiClientDataTable::class)
        ->assertCanSeeTableRecords([$this->apiClient, $open])
        ->assertTableColumnStateSet('active_tokens_count', 2, $this->apiClient)
        ->assertTableColumnStateSet('tokens_max_last_used_at', now()->subHour(), $this->apiClient)
        ->assertTableColumnStateSet('enabled', true, $this->apiClient)
        ->assertTableColumnStateSet('enabled', false, $open)
        ->assertTableColumnStateSet('allows_any_ip_address', false, $this->apiClient)
        ->assertTableColumnStateSet('allows_any_ip_address', true, $open)
        ->assertTableColumnStateSet('service', 'billing', $open);
});

test('the list searches by name', function (): void {
    $other = ApiClient::factory()->create(['name' => 'Payroll']);

    Livewire::test(ApiClientDataTable::class)
        ->searchTable('Payroll')
        ->assertCanSeeTableRecords([$other])
        ->assertCanNotSeeTableRecords([$this->apiClient]);
});

test('the table refuses a user without view-any', function (): void {
    revokePermissions($this->actor, 'system.api-clients.view-any');

    Livewire::test(ApiClientDataTable::class)->assertForbidden();
});

test('the create action goes to the create page', function (): void {
    Livewire::test(ApiClientDataTable::class)
        ->callAction(TestAction::make('create')->table())
        ->assertRedirect(route('system.api-clients.create'));
});

test('the create action is hidden without create', function (): void {
    revokePermissions($this->actor, 'system.api-clients.create');

    Livewire::test(ApiClientDataTable::class)->assertActionHidden(TestAction::make('create')->table());
});

test('the row click opens the API client', function (): void {
    grantPermissions($this->actor, 'system.api-clients.view');

    Livewire::test(ApiClientDataTable::class)
        ->callAction(TestAction::make('view')->table($this->apiClient))
        ->assertRedirect(route('system.api-clients.show', $this->apiClient));
});

test('the delete action deletes the API client and revokes its API tokens', function (): void {
    $apiToken = ApiToken::factory()->for($this->apiClient)->create();

    Livewire::test(ApiClientDataTable::class)
        ->callAction(TestAction::make('delete')->table($this->apiClient));

    expect($this->apiClient->fresh()->trashed())->toBeTrue()
        ->and($apiToken->fresh()->revoked)->toBeTrue();
});

test('the delete action is hidden without delete', function (): void {
    revokePermissions($this->actor, 'system.api-clients.delete');

    Livewire::test(ApiClientDataTable::class)->assertActionHidden(TestAction::make('delete')->table($this->apiClient));
});
