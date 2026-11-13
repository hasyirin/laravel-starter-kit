<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\System\ApiClient\Create;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'system.access', 'system.api-clients.create');
    $this->actingAs($this->actor);
});

function apiClientCreate(): Testable
{
    return Livewire::test(Create::class)
        ->set('form.name', 'Billing')
        ->set('form.allowed_ip_addresses', '192.0.2.10');
}

test('creating an API client needs create', function (): void {
    revokePermissions($this->actor, 'system.api-clients.create');

    $this->get(route('system.api-clients.create'))->assertForbidden();

    grantPermissions($this->actor, 'system.api-clients.create');

    $this->get(route('system.api-clients.create'))->assertOk()->assertSeeLivewire(Create::class);
});

test('saving creates the API client and opens its page', function (): void {
    config(['system.api.services' => ['billing' => ['base_url' => 'https://billing.test']]]);

    Livewire::test(Create::class)
        ->set('form.name', 'Billing')
        ->set('form.description', 'Sends invoices')
        ->set('form.enabled', false)
        ->set('form.allowed_ip_addresses', "192.0.2.10\n\n 198.51.100.0/24 \r\n")
        ->set('form.rate_limit_per_minute', 120)
        ->set('form.service', 'billing')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'API client created.')
        ->assertRedirect(route('system.api-clients.show', ApiClient::query()->where('name', 'Billing')->firstOrFail()));

    expect(ApiClient::query()->where('name', 'Billing')->firstOrFail()->only('description', 'enabled', 'allowed_ip_addresses', 'allows_any_ip_address', 'rate_limit_per_minute', 'service'))->toBe([
        'description' => 'Sends invoices',
        'enabled' => false,
        'allowed_ip_addresses' => ['192.0.2.10', '198.51.100.0/24'],
        'allows_any_ip_address' => false,
        'rate_limit_per_minute' => 120,
        'service' => 'billing',
    ]);
});

test('a new API client starts enabled with a rate limit of 60 calls per minute', function (): void {
    Livewire::test(Create::class)
        ->assertSet('form.enabled', true)
        ->assertSet('form.rate_limit_per_minute', 60)
        ->assertSet('form.allows_any_ip_address', false);
});

test('the name is required', function (): void {
    apiClientCreate()
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required']);
});

test('the name must be unique among API clients that are not deleted', function (): void {
    ApiClient::factory()->create(['name' => 'Billing']);
    ApiClient::factory()->create(['name' => 'Gone'])->delete();

    apiClientCreate()
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique']);

    apiClientCreate()
        ->set('form.name', 'Gone')
        ->call('save')
        ->assertHasNoErrors();
});

test('the name has at most 255 characters', function (): void {
    apiClientCreate()
        ->set('form.name', str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['form.name' => 'max']);
});

test('each allowed IP address must be an IP address or a CIDR range', function (): void {
    apiClientCreate()
        ->set('form.allowed_ip_addresses', "192.0.2.10\nlocalhost")
        ->call('save')
        ->assertHasErrors('form.allowed_ip_addresses')
        ->assertSee('localhost is not an IP address or a CIDR range of at least 1 bit. To allow every address, turn on &quot;Allow any IP address&quot;.', false);

    expect(ApiClient::query()->exists())->toBeFalse();
});

test('a range of every address is refused, so only the switch allows every address', function (): void {
    apiClientCreate()
        ->set('form.allowed_ip_addresses', '0.0.0.0/0')
        ->call('save')
        ->assertHasErrors('form.allowed_ip_addresses');
});

test('the rate limit is a whole number of at least 1', function (mixed $rateLimit, string $rule): void {
    apiClientCreate()
        ->set('form.rate_limit_per_minute', $rateLimit)
        ->call('save')
        ->assertHasErrors(['form.rate_limit_per_minute' => $rule]);
})->with([
    'empty' => [null, 'required'],
    'zero' => [0, 'min'],
]);

test('the service must be a service that the app declares', function (): void {
    config(['system.api.services' => ['billing' => ['base_url' => 'https://billing.test']]]);

    apiClientCreate()
        ->set('form.service', 'payroll')
        ->call('save')
        ->assertHasErrors(['form.service' => 'in']);
});

test('the service must not be linked to another API client that is not deleted', function (): void {
    config(['system.api.services' => ['billing' => ['base_url' => 'https://billing.test'], 'maps' => ['base_url' => 'https://maps.test']]]);
    ApiClient::factory()->create(['service' => 'billing']);
    ApiClient::factory()->create(['service' => 'maps'])->delete();

    apiClientCreate()
        ->set('form.service', 'billing')
        ->call('save')
        ->assertHasErrors(['form.service' => 'unique'])
        ->assertSee('The service has already been taken.');

    apiClientCreate()
        ->set('form.service', 'maps')
        ->call('save')
        ->assertHasNoErrors();
});

test('the service field shows only when the app declares a service', function (): void {
    Livewire::test(Create::class)->assertDontSeeHtml('wire:model="form.service"');

    config(['system.api.services' => ['billing' => ['base_url' => 'https://billing.test']]]);

    Livewire::test(Create::class)->assertSeeHtml('wire:model="form.service"');
});
