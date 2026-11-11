<?php

declare(strict_types=1);

use App\Livewire\Components\Navigation\Style;

return [
    'timezone' => 'Asia/Kuala_Lumpur',

    'locale' => 'en-MY',

    'locales' => ['en-MY', 'ms-MY', 'en-GB', 'en-US'],

    'country' => 'MY',

    'navigation' => [
        'app' => Style::Sidebar,
    ],

    'owner' => [
        'organization' => ['code' => 'OWNER', 'name' => 'Owner Organization'],
        'department' => ['code' => 'MAIN', 'name' => 'Main Department'],
        'branch' => ['code' => 'HQ', 'name' => 'Headquarters'],
    ],

    'audit' => [
        'retention_days' => env('AUDIT_RETENTION_DAYS'),
        'archive_disk' => env('AUDIT_ARCHIVE_DISK', 'local'),
        'queue_connection' => env('AUDIT_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
    ],

    'mail_log' => [
        'retention_days' => env('MAIL_RETENTION_DAYS'),
        'archive_disk' => env('MAIL_ARCHIVE_DISK', 'local'),
        'queue_connection' => env('MAIL_LOG_QUEUE_CONNECTION', env('AUDIT_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync'))),
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
                'organizations' => [
                    'view-any' => 'internal.controls.organizations.view-any',
                    'view' => 'internal.controls.organizations.view',
                    'create' => 'internal.controls.organizations.create',
                    'update' => 'internal.controls.organizations.update',
                    'delete' => 'internal.controls.organizations.delete',
                    'view-audit-log' => 'internal.controls.organizations.view-audit-log',
                ],
                'geography' => [
                    'view-any' => 'internal.controls.geography.view-any',
                    'view' => 'internal.controls.geography.view',
                    'create' => 'internal.controls.geography.create',
                    'update' => 'internal.controls.geography.update',
                    'delete' => 'internal.controls.geography.delete',
                    'view-audit-log' => 'internal.controls.geography.view-audit-log',
                ],
            ],
        ],
        'system' => [
            'access' => 'system.access',
            'horizon' => [
                'view' => 'system.horizon.view',
            ],
            'logs' => [
                'audits' => [
                    'view' => 'system.logs.audits.view',
                ],
                'mail' => [
                    'view' => 'system.logs.mail.view',
                    'resend' => 'system.logs.mail.resend',
                ],
            ],
        ],
    ],
];
