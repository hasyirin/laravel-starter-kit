<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Gateways;

use App\Domain\Shared\Sms\Contracts\SmsGateway;
use RuntimeException;
use SensitiveParameter;

class LogGateway implements SmsGateway
{
    public function send(#[SensitiveParameter] string $phone, #[SensitiveParameter] string $message): void
    {
        throw_if(app()->isProduction(), RuntimeException::class, 'The log SMS gateway cannot send SMS in production. Set SMS_GATEWAY to the gateway of the app.');

        logger()->info('SMS sent.', ['to' => $phone, 'message' => $message]);
    }
}
