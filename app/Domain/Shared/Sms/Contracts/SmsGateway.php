<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Contracts;

use SensitiveParameter;

interface SmsGateway
{
    public function send(#[SensitiveParameter] string $phone, string $message): void;
}
