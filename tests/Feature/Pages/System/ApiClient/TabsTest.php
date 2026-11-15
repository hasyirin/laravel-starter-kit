<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\ApiLogDataTable;
use App\Livewire\Pages\System\ApiCallModal;
use App\Livewire\Pages\System\ApiClient\ApiLog;
use App\Livewire\Pages\System\ApiClient\Audit;
use App\Livewire\Pages\System\ApiClient\Tabs;
use App\Livewire\Pages\System\ApiClient\Tokens;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'system.access', 'system.api-clients.view');
    $this->actingAs($this->actor);
    $this->apiClient = ApiClient::factory()->create();
});

test('the API tokens tab is the first tab', function (): void {
    Livewire::test(Tabs::class, ['apiClient' => $this->apiClient])
        ->assertSeeLivewire(Tokens::class);
});

test('the audit tab shows with view-audit-log', function (): void {
    Livewire::test(Tabs::class, ['apiClient' => $this->apiClient])
        ->assertDontSee(__('api/api-clients.tabs.audit'));

    grantPermissions($this->actor, 'system.api-clients.view-audit-log');

    Livewire::withQueryParams(['tab' => 'audit'])
        ->test(Tabs::class, ['apiClient' => $this->apiClient])
        ->assertSeeLivewire(Audit::class);
});

test('the API log tab shows with system.logs.api.view', function (): void {
    Livewire::test(Tabs::class, ['apiClient' => $this->apiClient])
        ->assertDontSee(__('api/api-clients.tabs.api_log'));

    grantPermissions($this->actor, 'system.logs.api.view');

    Livewire::withQueryParams(['tab' => 'api-log'])
        ->test(Tabs::class, ['apiClient' => $this->apiClient])
        ->assertSeeLivewire(ApiLog::class);
});

test('the API log tab shows the API log of the API client with the call modal', function (): void {
    grantPermissions($this->actor, 'system.logs.api.view');

    Livewire::test(ApiLog::class, ['apiClient' => $this->apiClient])
        ->assertSeeLivewire(ApiLogDataTable::class)
        ->assertSeeLivewire(ApiCallModal::class);
});

test('the API log tab needs system.logs.api.view', function (): void {
    Livewire::test(ApiLog::class, ['apiClient' => $this->apiClient])->assertForbidden();
});
