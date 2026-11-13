<?php

declare(strict_types=1);

return [
    'api_token' => 'API token',
    'ending' => 'ending in :last_four',
    'last_four' => 'Ends in',
    'scopes' => 'Scopes',
    'no_scopes' => 'No scope',
    'no_declared_scopes' => 'The app declares no scopes, so the API token can call only the routes that need no scope.',
    'created_at' => 'Created',
    'expires_at' => 'Expires',
    'never' => 'Never',
    'last_used_at' => 'Last used',
    'state' => 'State',
    'states' => [
        'active' => 'Active',
        'expires_soon' => 'Expires soon',
        'expired' => 'Expired',
        'revoked' => 'Revoked',
    ],
    'never_expires' => 'Never expires',
    'expiry_description' => 'When it is off, the API token expires in :days days.',
    'issue' => 'Issue',
    'issued_heading' => 'Copy the API token',
    'issued_warning' => 'You cannot see this API token again after you close this dialog. Copy it and keep it in a safe place now.',
    'rotate' => 'Rotate',
    'rotate_heading' => 'Rotate this API token?',
    'rotate_description' => 'A new API token gets the same scopes. The old API token :ending works for :days more days, or until it expires if that is sooner.',
    'revoke' => 'Revoke',
    'revoke_heading' => 'Revoke this API token?',
    'revoke_description' => 'The API rejects each call with this API token from now on.',
    'revoked' => 'The API token is revoked.',
];
