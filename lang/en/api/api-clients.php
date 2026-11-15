<?php

declare(strict_types=1);

return [
    'token_limit' => 'An API client can have at most :max active API tokens. Revoke one first.',
    'title' => 'API clients',
    'api_client' => 'API client',
    'details' => 'API client details',
    'description' => 'Description',
    'enabled' => 'Enabled',
    'enabled_description' => 'When it is off, the API rejects every call of this API client. The app still calls the service of this API client.',
    'rate_limit' => 'Rate limit',
    'per_minute' => 'calls per minute',
    'service' => 'Service',
    'no_service' => 'No service',
    'service_description' => 'The API log rows of calls to this service also show this API client.',
    'allowed_ip_addresses' => 'Allowed IP addresses',
    'allowed_ip_addresses_description' => 'An IP address or a CIDR range on each row, such as 192.0.2.10 or 198.51.100.0/24.',
    'allowed_ip_address' => 'Allowed IP address',
    'ip_address' => 'IP address',
    'invalid_ip_address' => 'Enter an IP address or a CIDR range, such as 198.51.100.0/24. To allow every address, turn on "Allow any IP address".',
    'allows_any_ip_address' => 'Allow any IP address',
    'any_ip_address' => 'Any IP address',
    'any_ip_address_warning' => 'Each IP address can call the API with an API token of this API client.',
    'no_ip_address_note' => 'The list is empty, so the API rejects every call of this API client.',
    'active_tokens_count' => 'Active API tokens',
    'last_used_at' => 'Last used',
    'tabs' => [
        'tokens' => 'API tokens',
        'api_log' => 'API log',
        'audit' => 'Audit',
    ],
    'create' => [
        'title' => 'Create API client',
    ],
];
