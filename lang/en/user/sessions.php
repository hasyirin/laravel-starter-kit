<?php

declare(strict_types=1);

return [
    'heading' => 'Sessions',
    'description' => 'The browsers where this account is signed in',
    'browser' => 'Browser',
    'browser_on_platform' => ':browser on :platform',
    'ip_address' => 'IP address',
    'last_activity_at' => 'Last activity',
    'current' => 'This session',
    'impersonating' => 'Impersonating :name',
    'unknown' => 'Unknown',
    'end' => [
        'button' => 'End session',
        'heading' => 'End the session?',
        'description' => 'The browser of the session signs out.',
        'ended' => 'The session is ended.',
    ],
    'end_all' => [
        'button' => 'End all other sessions',
        'heading' => 'End all other sessions?',
        'description' => 'The browsers of these sessions sign out.',
        'ended' => 'The other sessions are ended.',
    ],
    'end_all_of_user' => [
        'button' => 'End all sessions',
        'heading' => 'End all sessions of this user?',
        'description' => 'Each browser in this list signs out.',
        'ended' => 'All sessions of the user are ended.',
    ],
];
