<?php

declare(strict_types=1);

return [
    'locale' => 'en-MY',

    'country' => 'MY',

    'formats' => [
        'datetime' => 'd/m/Y, h:i A',
        'date' => 'd/m/Y',
    ],

    'owner' => [
        'organization' => ['code' => 'OWNER', 'name' => 'Owner Organization'],
        'department' => ['code' => 'MAIN', 'name' => 'Main Department'],
        'branch' => ['code' => 'HQ', 'name' => 'Headquarters'],
    ],

    'roles' => [
        'super-admin' => 'super-admin',
    ],

    'permissions' => [
        'bypass-authorization' => 'bypass-authorization',
        'internal' => [
            'access' => 'internal.access',
        ],
        'admin' => [
            'access' => 'admin.access',
            'horizon' => [
                'view' => 'admin.horizon.view',
            ],
            'controls' => [
                'roles' => [
                    'view-any' => 'admin.controls.roles.view-any',
                    'view' => 'admin.controls.roles.view',
                    'create' => 'admin.controls.roles.create',
                    'update' => 'admin.controls.roles.update',
                    'update-permissions' => 'admin.controls.roles.update-permissions',
                    'delete' => 'admin.controls.roles.delete',
                ],
                'users' => [
                    'view-any' => 'admin.controls.users.view-any',
                    'view' => 'admin.controls.users.view',
                    'create' => 'admin.controls.users.create',
                    'update' => 'admin.controls.users.update',
                    'update-permissions' => 'admin.controls.users.update-permissions',
                    'update-roles' => 'admin.controls.users.update-roles',
                    'delete' => 'admin.controls.users.delete',
                ],
            ],
        ],
    ],
];
