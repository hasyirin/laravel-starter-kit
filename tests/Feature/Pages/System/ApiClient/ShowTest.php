<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\System\ApiClient\Show;
use App\Livewire\Pages\System\ApiClient\Tabs;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'system.access', 'system.api-clients.view', 'system.api-clients.update');
    $this->actingAs($this->actor);
    $this->apiClient = ApiClient::factory()->create(['name' => 'Billing', 'allowed_ip_addresses' => ['192.0.2.10', '198.51.100.0/24']]);
});

test('viewing an API client needs view', function (): void {
    revokePermissions($this->actor, 'system.api-clients.view');

    $this->get(route('system.api-clients.show', $this->apiClient))->assertForbidden();

    grantPermissions($this->actor, 'system.api-clients.view');

    $this->get(route('system.api-clients.show', $this->apiClient))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeLivewire(Tabs::class)
        ->assertSee('<title>Billing - '.config('app.name').'</title>', false);
});

test('the browser cannot change the API client of the page', function (): void {
    Livewire::test(Show::class, ['apiClient' => $this->apiClient])->set('apiClient', ApiClient::factory()->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('the browser cannot change the API client of the form', function (): void {
    Livewire::test(Show::class, ['apiClient' => $this->apiClient])->set('form.apiClient', ApiClient::factory()->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('the form shows each allowed IP address on its own line', function (): void {
    Livewire::test(Show::class, ['apiClient' => $this->apiClient])
        ->assertSet('form.allowed_ip_addresses', "192.0.2.10\n198.51.100.0/24");
});

test('saving changes the API client and keeps its own name', function (): void {
    Livewire::test(Show::class, ['apiClient' => $this->apiClient])
        ->set('form.allowed_ip_addresses', '203.0.113.5')
        ->set('form.rate_limit_per_minute', 30)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'API client updated.');

    expect($this->apiClient->fresh()->only('name', 'allowed_ip_addresses', 'rate_limit_per_minute'))->toBe([
        'name' => 'Billing',
        'allowed_ip_addresses' => ['203.0.113.5'],
        'rate_limit_per_minute' => 30,
    ]);
});

test('saving keeps the service that the API client itself is linked to', function (): void {
    config(['system.api.services' => ['billing' => ['base_url' => 'https://billing.test']]]);
    $this->apiClient->update(['service' => 'billing']);

    Livewire::test(Show::class, ['apiClient' => $this->apiClient])
        ->set('form.rate_limit_per_minute', 30)
        ->call('save')
        ->assertHasNoErrors();
});

test('the form is read-only without update', function (): void {
    revokePermissions($this->actor, 'system.api-clients.update');

    Livewire::test(Show::class, ['apiClient' => $this->apiClient])
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'Changed')
        ->call('save')
        ->assertForbidden();

    expect($this->apiClient->fresh()->name)->toBe('Billing');
});

test('the page warns while any IP address is allowed', function (): void {
    Livewire::test(Show::class, ['apiClient' => $this->apiClient])
        ->assertDontSee(__('api/api-clients.any_ip_address_warning'))
        ->set('form.allows_any_ip_address', true)
        ->assertSee(__('api/api-clients.any_ip_address_warning'));
});

test('the page says that every call is rejected while no IP address is allowed', function (): void {
    Livewire::test(Show::class, ['apiClient' => $this->apiClient])
        ->assertDontSee(__('api/api-clients.no_ip_address_note'))
        ->set('form.allowed_ip_addresses', "  \n")
        ->assertSee(__('api/api-clients.no_ip_address_note'))
        ->set('form.allows_any_ip_address', true)
        ->assertDontSee(__('api/api-clients.no_ip_address_note'));
});
