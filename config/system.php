<?php

declare(strict_types=1);

return [
    'timezone' => 'Asia/Kuala_Lumpur',

    'locale' => 'en-MY',

    'locales' => ['en-MY', 'ms-MY', 'en-GB', 'en-US'],

    'country' => 'MY',

    'owner' => [
        'organization' => ['code' => 'OWNER', 'name' => 'Owner Organization'],
        'department' => ['code' => 'MAIN', 'name' => 'Main Department'],
        'branch' => ['code' => 'HQ', 'name' => 'Headquarters'],
    ],

    'roles' => [
        'system-admin' => 'system-admin',
    ],

    'permissions' => [
        'bypass-authorization' => 'bypass-authorization',
        'internal' => [
            'access' => 'internal.access',
            'controls' => [
                'roles' => [
                    'view-any' => 'internal.controls.roles.view-any',
                    'view' => 'internal.controls.roles.view',
                    'create' => 'internal.controls.roles.create',
                    'update' => 'internal.controls.roles.update',
                    'update-permissions' => 'internal.controls.roles.update-permissions',
                    'delete' => 'internal.controls.roles.delete',
                    'view-audit-log' => 'internal.controls.roles.view-audit-log',
                ],
                'users' => [
                    'view-any' => 'internal.controls.users.view-any',
                    'view' => 'internal.controls.users.view',
                    'create' => 'internal.controls.users.create',
                    'update' => 'internal.controls.users.update',
                    'update-permissions' => 'internal.controls.users.update-permissions',
                    'update-roles' => 'internal.controls.users.update-roles',
                    'delete' => 'internal.controls.users.delete',
                    'impersonate' => 'internal.controls.users.impersonate',
                    'view-audit-log' => 'internal.controls.users.view-audit-log',
                ],
            ],
        ],
        'system' => [
            'access' => 'system.access',
            'horizon' => [
                'view' => 'system.horizon.view',
            ],
        ],
    ],
];
