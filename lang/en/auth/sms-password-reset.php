<?php

declare(strict_types=1);

return [
    'title' => 'Reset password by SMS',
    'heading' => 'Reset password by SMS',
    'description' => 'Enter your phone number, and we will send a code to it.',
    'send' => 'Send code',
    'sent' => 'If this number belongs to a user with a verified phone number, we sent a code to it.',
    'resend' => 'Resend code',
    'throttled' => 'Too many codes. Try again in :seconds seconds.',
    'mail' => [
        'subject' => 'Password reset by SMS',
        'line' => 'Someone used an SMS code to start a password reset for your account.',
        'warning' => 'If this was not you, reset your password by email now.',
    ],
];
