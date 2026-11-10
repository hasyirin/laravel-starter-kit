<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Gateways;

use App\Domain\Shared\Sms\Contracts\SmsGateway;
use RuntimeException;

class LogGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        throw_if(app()->isProduction(), RuntimeException::class, 'The log SMS gateway cannot send SMS in production. Set system.sms.gateway to the gateway of the app.');

        logger()->info('SMS sent.', ['to' => $phone, 'message' => $message]);
    }
}
