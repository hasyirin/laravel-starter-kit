<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Contracts;

interface SmsGateway
{
    public function send(string $phone, string $message): void;
}
