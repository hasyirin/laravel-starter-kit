<?php

declare(strict_types=1);

return [
    'directions' => [
        'inbound' => 'Inbound',
        'outbound' => 'Outbound',
    ],
    'outcomes' => [
        'ok' => 'OK',
        'missing_token' => 'Missing token',
        'invalid_token' => 'Invalid token',
        'expired_token' => 'Expired token',
        'revoked_token' => 'Revoked token',
        'client_disabled' => 'API client disabled',
        'ip_not_allowed' => 'IP address not allowed',
        'missing_scope' => 'Missing scope',
        'rate_limited' => 'Rate limited',
        'validation_failed' => 'Validation failed',
        'client_error' => 'Client error',
        'server_error' => 'Server error',
        'connection_failed' => 'Connection failed',
    ],
];
