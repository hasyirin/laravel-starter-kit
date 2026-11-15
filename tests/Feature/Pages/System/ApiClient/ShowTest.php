<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Actions\SaveApiClient;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\System\ApiClient\Show;
use App\Livewire\Pages\System\ApiClient\Tabs;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'system.access', 'system.api-clients.view', 'system.api-clients.update');
    $this->actingAs($this->actor);
    $this->apiClient = ApiClient::factory()->create(['name' => 'Billing', 'allowed_ip_addresses' => [
        ['address' => '192.0.2.10', 'description' => 'Head office'],
        ['address' => '198.51.100.0/24', 'description' => null],
    ]]);
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

test('the form shows each allowed IP address as a row with its description', function (): void {
    $component = Livewire::test(Show::class, ['apiClient' => $this->apiClient])
        ->assertSet('form.allowed_ip_addresses', [
            ['address' => '192.0.2.10', 'description' => 'Head office'],
            ['address' => '198.51.100.0/24', 'description' => ''],
        ]);
    $document = HTMLDocument::createFromString($component->html(), LIBXML_NOERROR);

    $rows = $document->querySelector('[data-row-add]')?->closest('[x-data]')?->getAttribute('x-data');
    $removes = collect($document->querySelectorAll('[data-row-remove]'))->map(fn (Element $button): ?string => $button->getAttribute('x-on:click'))->all();

    expect($document->querySelector('input[wire\:model="form.allowed_ip_addresses.1.address"]'))->not->toBeNull()
        ->and($document->querySelector('input[wire\:model="form.allowed_ip_addresses.1.description"]'))->not->toBeNull()
        ->and($rows)->toBe("repeatableRows('form.allowed_ip_addresses')")
        ->and($document->querySelector('[data-row-add]')?->textContent)->toContain('Allowed IP address')
        ->and($removes)->toBe(['removeRow(0)', 'removeRow(1)']);
});

test('saving changes the API client and keeps its own name', function (): void {
    Livewire::test(Show::class, ['apiClient' => $this->apiClient])
        ->set('form.allowed_ip_addresses', [['address' => '203.0.113.5', 'description' => 'Branch']])
        ->set('form.rate_limit_per_minute', 30)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'API client updated.');

    expect($this->apiClient->fresh()->only('name', 'allowed_ip_addresses', 'rate_limit_per_minute'))->toBe([
        'name' => 'Billing',
        'allowed_ip_addresses' => [['address' => '203.0.113.5', 'description' => 'Branch']],
        'rate_limit_per_minute' => 30,
    ]);
});

test('a save that the Action class refuses rolls back, shows the error on the form field, and keeps the API client as stored', function (): void {
    $this->mock(SaveApiClient::class)->expects('execute')->andReturnUsing(function (ApiClient $apiClient): never {
        $apiClient->forceFill(['name' => 'Changed'])->save();

        throw ValidationException::withMessages(['name' => 'The name is taken.']);
    });

    $form = Livewire::test(Show::class, ['apiClient' => $this->apiClient])
        ->set('form.name', 'Changed')
        ->call('save')
        ->assertHasErrors(['form.name' => 'The name is taken.'])
        ->instance()->form;

    expect($this->apiClient->fresh()?->name)->toBe('Billing')
        ->and($form->apiClient?->name)->toBe('Billing');
});

test('saving keeps the service that the API client itself is linked to', function (): void {
    config(['system.api.services' => ['billing' => ['base_url' => 'https://billing.test']]]);
    $this->apiClient->update(['service' => 'billing']);

    Livewire::test(Show::class, ['apiClient' => $this->apiClient])
        ->set('form.rate_limit_per_minute', 30)
        ->call('save')
        ->assertHasNoErrors();
});

test('a service that the app no longer declares still shows, and saving is refused until the system administrator clears it', function (): void {
    config(['system.api.services' => []]);
    $this->apiClient->update(['service' => 'billing']);

    Livewire::test(Show::class, ['apiClient' => $this->apiClient])
        ->assertSeeHtml('wire:model="form.service"')
        ->assertSeeHtml('value="billing"')
        ->set('form.enabled', false)
        ->call('save')
        ->assertHasErrors(['form.service' => 'in'])
        ->assertSee('The selected service is invalid.')
        ->set('form.service', null)
        ->call('save')
        ->assertHasNoErrors();

    expect($this->apiClient->fresh()->only('enabled', 'service'))->toBe(['enabled' => false, 'service' => null]);
});

test('the form is read-only without update', function (): void {
    revokePermissions($this->actor, 'system.api-clients.update');

    Livewire::test(Show::class, ['apiClient' => $this->apiClient])
        ->assertDontSeeHtml('type="submit"')
        ->assertDontSeeHtml('data-row-add')
        ->assertDontSeeHtml('data-row-remove')
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
        ->set('form.allowed_ip_addresses', [])
        ->assertSee(__('api/api-clients.no_ip_address_note'))
        ->set('form.allows_any_ip_address', true)
        ->assertDontSee(__('api/api-clients.no_ip_address_note'));
});
