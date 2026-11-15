<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\System\ApiCallModal;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->viewer = User::factory()->create();
    grantPermissions($this->viewer, 'system.access', 'system.logs.api.view');
    $this->actingAs($this->viewer);
});

test('the modal of an inbound call shows every stored field and only the last 4 characters of the API token', function (): void {
    $apiClient = ApiClient::factory()->create(['name' => 'Payroll']);
    $apiToken = ApiToken::factory()->for($apiClient)->create(['last_four' => 'wxyz']);
    $apiCall = ApiCall::factory()->byClient($apiClient)->create([
        'api_token_id' => $apiToken->id,
        'method' => 'POST',
        'url' => '/api/v1/orders',
        'route_name' => 'api.v1.orders.store',
        'status' => 201,
        'ip_address' => '192.0.2.10',
        'user_agent' => 'Payroll/1.0',
        'trace_id' => 'trace-123',
        'request_size' => 120,
        'response_size' => 64,
        'request_body' => '{"amount":10}',
        'response_body' => '{"id":7}',
    ]);

    Livewire::test(ApiCallModal::class)
        ->call('show', id: $apiCall->id)
        ->assertSee(['POST /api/v1/orders', 'Payroll/1.0'])
        ->assertSeeInOrder(['Payroll', '192.0.2.10', 'trace-123', 'api.v1.orders.store', '120 bytes', '64 bytes', 'Ending in wxyz', '{"amount":10}', '{"id":7}'])
        ->assertDontSee($apiToken->token_hash);
});

test('the modal of an outbound call shows the service, the related record and the error', function (): void {
    $user = User::factory()->create();
    $apiCall = ApiCall::factory()->connectionFailed()->create(['resource_type' => $user->getMorphClass(), 'resource_id' => $user->id]);

    Livewire::test(ApiCallModal::class)
        ->call('show', id: $apiCall->id)
        ->assertSee('billing')
        ->assertSee(__('api/api-calls.resource'))
        ->assertSee('#'.$user->id)
        ->assertSee('cURL error 7: Failed to connect.');
});

test('the modal escapes the stored bodies and the error', function (): void {
    $apiCall = ApiCall::factory()->create(['request_body' => '<b>Request</b>', 'response_body' => '<i>Response</i>', 'error' => '<u>Error</u>']);

    Livewire::test(ApiCallModal::class)
        ->call('show', id: $apiCall->id)
        ->assertSee('<b>Request</b>')
        ->assertDontSeeHtml('<b>Request</b>')
        ->assertDontSeeHtml('<i>Response</i>')
        ->assertDontSeeHtml('<u>Error</u>');
});

test('the modal does not find a call that does not exist', function (): void {
    Livewire::test(ApiCallModal::class)
        ->call('show', id: 999)
        ->assertNotFound();
});

test('the modal needs system.logs.api.view', function (): void {
    revokePermissions($this->viewer, 'system.logs.api.view');

    Livewire::test(ApiCallModal::class)->assertForbidden();
});
