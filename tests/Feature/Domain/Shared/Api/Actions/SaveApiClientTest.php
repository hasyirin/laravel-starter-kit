<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Actions\SaveApiClient;
use App\Domain\Shared\Api\Models\ApiClient;

test('saving creates and updates an API client', function (): void {
    $apiClient = SaveApiClient::make()->execute(
        new ApiClient,
        name: 'Billing',
        description: 'The billing system.',
        enabled: true,
        allowedIpAddresses: [['address' => '192.0.2.10', 'description' => 'Head office']],
        allowsAnyIpAddress: false,
        rateLimitPerMinute: 60,
        service: null,
    );

    expect($apiClient->exists)->toBeTrue();

    SaveApiClient::make()->execute(
        $apiClient,
        name: 'Billing system',
        description: null,
        enabled: false,
        allowedIpAddresses: [['address' => '198.51.100.0/24', 'description' => 'Data centre']],
        allowsAnyIpAddress: true,
        rateLimitPerMinute: 120,
        service: 'billing',
    );

    expect($apiClient->fresh()?->only('name', 'description', 'enabled', 'allowed_ip_addresses', 'allows_any_ip_address', 'rate_limit_per_minute', 'service'))->toBe([
        'name' => 'Billing system',
        'description' => null,
        'enabled' => false,
        'allowed_ip_addresses' => [['address' => '198.51.100.0/24', 'description' => 'Data centre']],
        'allows_any_ip_address' => true,
        'rate_limit_per_minute' => 120,
        'service' => 'billing',
    ]);
});

test('saving trims the allowed IP addresses, drops blank and repeated addresses, and stores a blank description as null', function (): void {
    $apiClient = SaveApiClient::make()->execute(
        new ApiClient,
        name: 'Billing',
        description: null,
        enabled: true,
        allowedIpAddresses: [
            ['address' => ' 192.0.2.10 ', 'description' => ' Head office '],
            ['address' => '', 'description' => 'Blank'],
            ['address' => '192.0.2.10', 'description' => 'Again'],
            ['address' => '2001:db8::/32', 'description' => ' '],
        ],
        allowsAnyIpAddress: false,
        rateLimitPerMinute: 60,
        service: null,
    );

    expect($apiClient->fresh()?->allowed_ip_addresses)->toBe([
        ['address' => '192.0.2.10', 'description' => 'Head office'],
        ['address' => '2001:db8::/32', 'description' => null],
    ]);
});

test('saving keeps a description of 0', function (): void {
    $apiClient = SaveApiClient::make()->execute(
        new ApiClient,
        name: 'Billing',
        description: null,
        enabled: true,
        allowedIpAddresses: [['address' => '192.0.2.10', 'description' => '0']],
        allowsAnyIpAddress: false,
        rateLimitPerMinute: 60,
        service: null,
    );

    expect($apiClient->fresh()?->allowed_ip_addresses)->toBe([['address' => '192.0.2.10', 'description' => '0']]);
});
