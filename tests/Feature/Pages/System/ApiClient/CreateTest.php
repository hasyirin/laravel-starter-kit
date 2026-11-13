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
        ->set('form.allowed_ip_addresses', [['address' => '192.0.2.10', 'description' => '']]);
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
        ->set('form.allowed_ip_addresses', [
            ['address' => '192.0.2.10', 'description' => 'Head office'],
            ['address' => ' 198.51.100.0/24 ', 'description' => ''],
        ])
        ->set('form.rate_limit_per_minute', 120)
        ->set('form.service', 'billing')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'API client created.')
        ->assertRedirect(route('system.api-clients.show', ApiClient::query()->where('name', 'Billing')->firstOrFail()));

    expect(ApiClient::query()->where('name', 'Billing')->firstOrFail()->only('description', 'enabled', 'allowed_ip_addresses', 'allows_any_ip_address', 'rate_limit_per_minute', 'service'))->toBe([
        'description' => 'Sends invoices',
        'enabled' => false,
        'allowed_ip_addresses' => [
            ['address' => '192.0.2.10', 'description' => 'Head office'],
            ['address' => '198.51.100.0/24', 'description' => null],
        ],
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

test('each allowed IP address must be an IP address or a CIDR range, with the error on its own row', function (): void {
    apiClientCreate()
        ->set('form.allowed_ip_addresses', [
            ['address' => 'localhost', 'description' => ''],
            ['address' => '192.0.2.10', 'description' => ''],
            ['address' => '192.0.2.300', 'description' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['form.allowed_ip_addresses.0.address', 'form.allowed_ip_addresses.2.address'])
        ->assertHasNoErrors('form.allowed_ip_addresses.1.address')
        ->assertSee('Enter an IP address or a CIDR range, such as 198.51.100.0/24. To allow every address, turn on &quot;Allow any IP address&quot;.', false);

    expect(ApiClient::query()->exists())->toBeFalse();
});

test('the error of an allowed IP address row shows one time, on its row', function (): void {
    $html = apiClientCreate()
        ->set('form.allowed_ip_addresses', [
            ['address' => 'localhost', 'description' => ''],
            ['address' => '192.0.2.10', 'description' => ''],
        ])
        ->call('save')
        ->html();

    expect(substr_count($html, 'Enter an IP address or a CIDR range'))->toBe(1);
});

test('a range of every address is refused, so only the switch allows every address', function (string $range): void {
    apiClientCreate()
        ->set('form.allowed_ip_addresses', [['address' => $range, 'description' => '']])
        ->call('save')
        ->assertHasErrors('form.allowed_ip_addresses.0.address');
})->with(['0.0.0.0/0', '::/0']);

test('an allowed IP address row needs an address', function (): void {
    apiClientCreate()
        ->set('form.allowed_ip_addresses', [['address' => ' ', 'description' => 'Head office']])
        ->call('save')
        ->assertHasErrors(['form.allowed_ip_addresses.0.address' => 'required'])
        ->assertSee('The IP address field is required.');
});

test('an allowed IP address row without an address key is refused', function (): void {
    apiClientCreate()
        ->set('form.allowed_ip_addresses', [['description' => 'Head office']])
        ->call('save')
        ->assertHasErrors(['form.allowed_ip_addresses.0.address' => 'required']);
});

test('an allowed IP address can be listed only once', function (): void {
    apiClientCreate()
        ->set('form.allowed_ip_addresses', [
            ['address' => '192.0.2.10', 'description' => 'Head office'],
            ['address' => '192.0.2.10', 'description' => 'Branch'],
        ])
        ->call('save')
        ->assertHasErrors(['form.allowed_ip_addresses.1.address' => 'distinct'])
        ->assertSee('The IP address field has a duplicate value.');
});

test('an allowed IP address that differs from another only by spaces is a duplicate', function (): void {
    apiClientCreate()
        ->set('form.allowed_ip_addresses', [
            ['address' => '192.0.2.10', 'description' => 'Head office'],
            ['address' => ' 192.0.2.10 ', 'description' => 'Branch'],
        ])
        ->call('save')
        ->assertHasErrors(['form.allowed_ip_addresses.1.address' => 'distinct']);

    expect(ApiClient::query()->exists())->toBeFalse();
});

test('an allowed IP address row without a description is saved without one', function (): void {
    apiClientCreate()
        ->set('form.allowed_ip_addresses', [['address' => '192.0.2.10']])
        ->call('save')
        ->assertHasNoErrors();

    expect(ApiClient::query()->sole()->allowed_ip_addresses)->toBe([['address' => '192.0.2.10', 'description' => null]]);
});

test('the description of an allowed IP address is optional and has at most 255 characters', function (): void {
    apiClientCreate()
        ->set('form.allowed_ip_addresses', [['address' => '192.0.2.10', 'description' => str_repeat('a', 256)]])
        ->call('save')
        ->assertHasErrors(['form.allowed_ip_addresses.0.description' => 'max']);

    apiClientCreate()
        ->set('form.allowed_ip_addresses', [['address' => '192.0.2.10', 'description' => '']])
        ->call('save')
        ->assertHasNoErrors();
});

test('an API client can be saved without an allowed IP address', function (): void {
    apiClientCreate()
        ->set('form.allowed_ip_addresses', [])
        ->call('save')
        ->assertHasNoErrors();

    expect(ApiClient::query()->sole()->allowed_ip_addresses)->toBeEmpty();
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
