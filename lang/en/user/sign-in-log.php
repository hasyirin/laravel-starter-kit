<?php

declare(strict_types=1);

return [
    'title' => 'Sign-in log',
    'sign_in_entry' => 'Sign-in entry',
    'method' => 'Method',
    'time' => 'Time',
    'from' => 'From',
    'until' => 'Until',
    'user' => 'User',
    'no_user' => 'Unknown',
    'event' => 'Event',
    'result' => 'Result',
    'two_factor' => '2FA',
    'impersonator' => 'Impersonator',
    'ip_address' => 'IP address',
    'user_agent' => 'User agent',
    'trace_id' => 'Trace ID',
    'events' => [
        'sign_in' => 'Sign-in',
        'failed_sign_in' => 'Failed sign-in',
        'sign_out' => 'Sign-out',
        'impersonation_started' => 'Impersonation started',
        'impersonation_stopped' => 'Impersonation stopped',
    ],
    'results' => [
        'signed_in' => 'Signed in',
        'wrong_password' => 'Wrong password',
        'no_matching_user' => 'No matching user',
        'wrong_two_factor_code' => 'Wrong two-factor code',
        'passkey_failed' => 'Passkey failed',
        'locked_out' => 'Locked out',
    ],
    'methods' => [
        'password' => 'Password',
        'passkey' => 'Passkey',
    ],
];
