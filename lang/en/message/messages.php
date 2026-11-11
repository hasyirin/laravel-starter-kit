<?php

declare(strict_types=1);

return [
    'status' => 'Status',
    'channel' => 'Channel',
    'channels' => [
        'mail' => 'Email',
        'sms' => 'SMS',
    ],
    'recipient' => 'Recipient',
    'columns' => [
        'content' => 'Content',
        'opened' => 'Opened',
        'clicked' => 'Clicked',
        'viewed' => 'Viewed',
        'resend' => 'Resend',
    ],
    'marks' => [
        'resend' => 'Resend',
        'resent' => '{1} Resent|[2,*] Resent :count times',
    ],
    'hints' => [
        'opened' => 'The email client loaded the images of the email. It is not proof that a person read the email.',
        'clicked' => 'Someone followed a link in the email. A link scanner can do this, so it is not proof that a person read the email.',
        'viewed' => 'The recipient, signed in, followed a link in the email. It is the only proof that the recipient read the email.',
        'resend' => 'A resend repeats an earlier message. "Resent" marks a message that staff sent again.',
        'started' => 'The time when the app started to send the message.',
        'sent' => 'The time when the mail server or the SMS gateway accepted the message. It is not proof that the message reached the person.',
    ],
    'no_subject' => 'No subject',
    'cc' => 'CC',
    'bcc' => 'BCC',
    'intercepted_to' => 'Intercepted to',
    'started_at' => 'Started',
    'sent_at' => 'Sent',
    'resend_of' => 'Resend of the message from',
    'resends' => 'Resends',
    'tracking' => 'Tracking',
    'viewed' => 'Viewed',
    'clicked' => 'May have clicked',
    'opened' => 'May have opened',
    'opened_times' => '{1} :time|[2,*] :time, :count times',
    'not_yet' => 'Not yet',
    'signals' => 'Only a view proves that the recipient read the email. Mail clients and link scanners can cause an open or a click.',
    'signals_with_copies' => 'Only a view proves that the recipient read the email. Mail clients and link scanners can cause an open or a click, and a copy recipient can cause one too.',
    'attachments' => 'Attachments',
    'body' => 'Body',
    'no_body' => 'The app does not keep the body of this email.',
    'text' => 'Text',
    'no_text' => 'The app does not keep the text of this message.',
    'resend_to' => 'Send to',
    'resend' => 'Resend',
    'resent' => 'The message is queued to send again.',
    'statuses' => [
        'sending' => 'Sending',
        'sent' => 'Sent',
        'failed' => 'Failed',
    ],
    'reasons' => [
        'timeout' => [
            'label' => 'The send took too long, so the app stopped it.',
            'suggestion' => 'The message may have arrived before the app stopped. Ask the recipient first, so that they do not get it twice.',
        ],
        'invalid_recipient' => [
            'label' => 'The email address of the recipient is not valid or does not exist.',
            'suggestion' => 'Check the spelling of the address, then resend the email to the correct address.',
        ],
        'mailbox_full' => [
            'label' => 'The mailbox of the recipient is full.',
            'suggestion' => 'Ask the recipient to empty their mailbox, or resend the email to another address.',
        ],
        'rejected' => [
            'label' => 'The mail server of the recipient rejected the email.',
            'suggestion' => 'Resend the email. If it fails again, resend it to another address.',
        ],
        'temporarily_rejected' => [
            'label' => 'The mail server rejected the email for a short time.',
            'suggestion' => 'This is usually temporary. Resend the email later.',
        ],
        'attachment_too_large' => [
            'label' => 'The attachments are too large.',
            'suggestion' => 'Make the files smaller, then resend the email.',
        ],
        'attachment_missing' => [
            'label' => 'An attachment is missing from the storage of the app.',
            'suggestion' => 'Make the file again, then resend the email.',
        ],
        'connection_failed' => [
            'label' => 'The app could not connect to its mail server.',
            'suggestion' => 'Resend the email later. If it fails again, tell a system administrator.',
        ],
        'authentication_failed' => [
            'label' => 'The mail server did not accept the login of the app.',
            'suggestion' => 'A resend fails until a system administrator corrects the mail settings.',
        ],
        'dropped' => [
            'label' => 'The app stopped the message before it was sent.',
            'suggestion' => 'Outside production, the intercept drops a message when it has no address or phone number to send it to.',
        ],
        'no_address' => [
            'label' => 'The recipient has no email address.',
            'suggestion' => 'Add an email address to the recipient, then send the email again.',
        ],
        'no_phone_number' => [
            'label' => 'The recipient has no phone number.',
            'suggestion' => 'Add a phone number to the recipient, then send the SMS again.',
        ],
        'calling_code_not_allowed' => [
            'label' => 'The app does not send SMS to the calling code of this phone number.',
            'suggestion' => 'Use a phone number with a calling code that the app sends SMS to.',
        ],
        'unknown' => [
            'label' => 'An unknown error occurred.',
            'suggestion' => 'Send the message again. If it fails again, give the technical message below to a system administrator.',
        ],
    ],
];
