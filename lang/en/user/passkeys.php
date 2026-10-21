<?php

declare(strict_types=1);

return [
    'heading' => 'Passkeys',
    'description' => 'Manage your passkeys for passwordless sign-in',
    'unsupported' => 'Passkeys are not supported in this browser.',
    'added' => 'Added :time',
    'last_used' => 'Last used :time',

    'empty' => [
        'heading' => 'No passkeys yet',
        'description' => 'Add a passkey to sign in without a password',
    ],

    'add' => [
        'button' => 'Add passkey',
        'name' => 'Passkey name',
        'name_placeholder' => 'e.g., MacBook Pro, iPhone',
        'name_help' => 'Give this passkey a name to help you identify it later.',
        'submit' => 'Register passkey',
        'loading' => 'Registering...',
    ],

    'remove' => [
        'heading' => 'Remove passkey',
        'description' => 'Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.',
        'submit' => 'Remove passkey',
    ],

    'verify' => [
        'button' => 'Sign in with a passkey',
        'loading' => 'Authenticating...',
        'separator' => 'Or continue with email',
    ],

    'confirm' => [
        'button' => 'Confirm with passkey',
        'loading' => 'Confirming...',
        'separator' => 'Or confirm with password',
    ],
];
