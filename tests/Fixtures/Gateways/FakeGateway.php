<?php

declare(strict_types=1);

namespace Tests\Fixtures\Gateways;

use App\Domain\Shared\Sms\Contracts\SmsGateway;

class FakeGateway implements SmsGateway
{
    /**
     * @var list<array{phone: string, message: string}>
     */
    public array $sent = [];

    public function send(string $phone, string $message): void
    {
        $this->sent[] = ['phone' => $phone, 'message' => $message];
    }
}
