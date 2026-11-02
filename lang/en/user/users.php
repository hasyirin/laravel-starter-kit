<?php

declare(strict_types=1);

return [
    'title' => 'Users',
    'roles' => 'Roles',
    'organization' => 'Organization',
    'branch' => 'Branch',
    'department' => 'Department',
    'position' => 'Position',
    'unverified' => 'Unverified',
    'mark_as_verified' => 'Mark as verified',
    'updated' => 'The user is saved.',
    'tabs' => [
        'roles' => 'Roles',
        'permissions' => 'Permissions',
    ],
    'roles_tab' => [
        'role' => 'Role',
        'system' => 'System',
        'attach' => 'Attach',
        'detach' => 'Detach',
        'detach_heading' => 'Detach this role?',
        'saved' => 'The roles are saved.',
    ],
    'permissions' => [
        'search' => 'Search permissions',
        'only_granted' => 'Only granted',
        'expand_all' => 'Expand all',
        'from' => 'from :role',
        'count' => ':granted of :total',
        'unsaved' => '{1} :count unsaved change|[2,*] :count unsaved changes',
        'saved' => 'The permissions are saved.',
    ],
    'create' => [
        'title' => 'Create user',
        'submit' => 'Create',
    ],
    'verify' => [
        'heading' => 'Mark the email as verified?',
        'description' => 'The user will not need to confirm :email.',
    ],
];
