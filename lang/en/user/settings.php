<?php

declare(strict_types=1);

return [
    'heading' => 'Settings',
    'description' => 'Manage your profile and account settings',

    'nav' => [
        'profile' => 'Profile',
        'security' => 'Security',
        'appearance' => 'Appearance',
    ],

    'profile' => [
        'title' => 'Profile settings',
        'heading' => 'Profile',
        'description' => 'Update your name and email address',
        'unverified' => 'Your email address is unverified.',
        'resend_verification' => 'Click here to re-send the verification email.',
        'verification_sent' => 'A new verification link has been sent to your email address.',
        'updated' => 'Profile updated.',
    ],

    'security' => [
        'title' => 'Security settings',
    ],

    'password' => [
        'heading' => 'Update password',
        'description' => 'Ensure your account is using a long, random password to stay secure',
        'updated' => 'Password updated.',
    ],

    'two_factor' => [
        'heading' => 'Two-factor authentication',
        'description' => 'Manage your two-factor authentication settings',
        'enabled_description' => 'You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.',
        'disabled_description' => 'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.',
        'enable' => 'Enable 2FA',
        'disable' => 'Disable 2FA',
        'manual_entry' => 'or, enter the code manually',
        'modal' => [
            'setup' => [
                'title' => 'Enable two-factor authentication',
                'description' => 'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.',
            ],
            'verify' => [
                'title' => 'Verify authentication code',
                'description' => 'Enter the 6-digit code from your authenticator app.',
            ],
            'enabled' => [
                'title' => 'Two-factor authentication enabled',
                'description' => 'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
            ],
        ],
    ],

    'recovery_codes' => [
        'heading' => '2FA recovery codes',
        'description' => 'Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.',
        'show' => 'View recovery codes',
        'hide' => 'Hide recovery codes',
        'regenerate' => 'Regenerate codes',
        'list' => 'Recovery codes',
        'help' => 'Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate codes above.',
    ],

    'appearance' => [
        'title' => 'Appearance settings',
        'heading' => 'Appearance',
        'description' => 'Update the appearance settings for your account',
        'light' => 'Light',
        'dark' => 'Dark',
        'system' => 'System',
    ],

    'delete_account' => [
        'heading' => 'Delete account',
        'description' => 'Delete your account and all of its resources',
        'button' => 'Delete account',
        'confirm' => [
            'heading' => 'Are you sure you want to delete your account?',
            'description' => 'Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
        ],
        'submit' => 'Delete account',
    ],
];
