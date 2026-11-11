<?php

declare(strict_types=1);

return [
    'title' => 'Users',
    'user' => 'User',
    'user_information' => 'User information',
    'roles' => 'Roles',
    'organization' => 'Organization',
    'placement' => 'Placement',
    'branch' => 'Branch',
    'department' => 'Department',
    'position' => 'Position',
    'created_at' => 'Created',
    'email_verified_at' => 'Email verified',
    'phone_verified_at' => 'Phone verified',
    'no_phone' => 'No phone number',
    'unverified' => 'Unverified',
    'mark_as_verified' => 'Mark as verified',
    'tabs' => [
        'passkeys' => 'Passkeys',
        'audit' => 'Audit',
    ],
    'passkeys_tab' => [
        'name' => 'Name',
        'authenticator' => 'Authenticator',
        'created_at' => 'Created',
        'last_used_at' => 'Last used',
        'revoke' => 'Revoke',
        'revoke_heading' => 'Revoke this passkey?',
        'revoked' => 'The passkey is revoked.',
    ],
    'audit' => [
        'by' => 'By this user',
        'on' => 'On this user',
    ],
    'permissions' => [
        'heading' => 'Permissions',
        'search' => 'Search permissions',
        'only_granted' => 'Only granted',
        'expand_all' => 'Expand all',
        'expand' => 'Expand :label',
        'collapse' => 'Collapse :label',
        'from' => 'from :role',
        'count' => ':granted of :total',
    ],
    'create' => [
        'title' => 'Create user',
    ],
    'impersonation' => [
        'action' => 'Impersonate',
        'heading' => 'Impersonate :name?',
        'description' => 'You will use the app as this user. Each change you make records you as the impersonator. Enter your password to continue.',
        'by' => 'Impersonated by :name',
        'stop' => 'Stop impersonating',
    ],
    'verify' => [
        'heading' => 'Mark the email as verified?',
        'description' => 'The user will not need to confirm :email.',
    ],
    'verify_phone' => [
        'heading' => 'Mark the phone number as verified?',
        'description' => 'The user will not need to confirm :phone.',
    ],
];
