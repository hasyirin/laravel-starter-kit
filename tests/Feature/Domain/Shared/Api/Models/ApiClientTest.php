<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Common\Models\AuditEntry;
use Illuminate\Database\UniqueConstraintViolationException;

test('an API client allows an IP address that its list holds as an address or a range', function (string $ipAddress, bool $allowed): void {
    $apiClient = ApiClient::factory()->make(['allowed_ip_addresses' => ['192.0.2.10', '198.51.100.0/24', '2001:db8::/32']]);

    expect($apiClient->allowsIpAddress($ipAddress))->toBe($allowed);
})->with([
    'an IPv4 address in the list' => ['192.0.2.10', true],
    'an IPv4 address in a range' => ['198.51.100.77', true],
    'an IPv6 address in a range' => ['2001:db8::1', true],
    'an IPv4 address outside the list' => ['192.0.2.11', false],
    'an IPv6 address outside the list' => ['2001:db9::1', false],
    'not an IP address' => ['localhost', false],
]);

test('an API client with an empty list allows no IP address, also not localhost', function (): void {
    $apiClient = ApiClient::factory()->make(['allowed_ip_addresses' => []]);

    expect($apiClient->allowsIpAddress('127.0.0.1'))->toBeFalse()
        ->and($apiClient->allowsIpAddress('::1'))->toBeFalse();
});

test('an API client that allows any IP address allows every IP address, also with an empty list', function (): void {
    $apiClient = ApiClient::factory()->anyIpAddress()->make(['allowed_ip_addresses' => []]);

    expect($apiClient->allowsIpAddress('203.0.113.5'))->toBeTrue()
        ->and($apiClient->allowsIpAddress('2001:db8::1'))->toBeTrue();
});

test('an API client can call with a scope only when its current token has that scope', function (): void {
    $apiClient = ApiClient::factory()->create();
    $apiToken = ApiToken::factory()->for($apiClient)->create(['scopes' => ['orders.read']]);

    expect($apiClient->tokenCan('orders.read'))->toBeFalse();

    $apiClient->withToken($apiToken);

    expect($apiClient->currentToken())->toBe($apiToken)
        ->and($apiClient->tokenCan('orders.read'))->toBeTrue()
        ->and($apiClient->tokenCan('orders.write'))->toBeFalse();
});

test('an API client is identified by its key and has no password or remember token', function (): void {
    $apiClient = ApiClient::factory()->create();

    expect($apiClient->getAuthIdentifier())->toBe($apiClient->id)
        ->and($apiClient->getAuthPassword())->toBeEmpty()
        ->and($apiClient->getRememberToken())->toBeNull();
});

test('the name of an API client is unique among API clients that are not deleted', function (): void {
    $deleted = ApiClient::factory()->create(['name' => 'Billing']);
    $deleted->delete();

    ApiClient::factory()->create(['name' => 'Billing']);

    expect(fn (): ApiClient => ApiClient::factory()->create(['name' => 'Billing']))->toThrow(UniqueConstraintViolationException::class);
});

test('a change to an API client goes to the audit log', function (): void {
    $apiClient = ApiClient::factory()->create();

    $apiClient->update(['enabled' => false]);

    $entry = AuditEntry::query()->where('subject_type', 'api_client')->where('event', 'updated')->sole();

    expect($entry->attribute_changes?->get('attributes'))->toBe(['enabled' => false]);
});
