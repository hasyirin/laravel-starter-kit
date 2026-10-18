<?php

declare(strict_types=1);

return [
    'formats' => [
        'datetime' => 'd/m/Y, h:i A',
        'date' => 'd/m/Y',
    ],

    'roles' => [
        'admin' => 'admin',
    ],

    'permissions' => [
        'bypass-authorization' => 'bypass-authorization',
        'admin' => [
            'access' => 'admin.access',
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
