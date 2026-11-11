<?php

declare(strict_types=1);

namespace Tests\Fixtures\Gateways;

use App\Domain\Shared\Sms\Contracts\SmsGateway;
use SensitiveParameter;

class FakeGateway implements SmsGateway
{
    /**
     * @var list<array{phone: string, message: string}>
     */
    public array $sent = [];

    public function send(#[SensitiveParameter] string $phone, #[SensitiveParameter] string $message): void
    {
        $this->sent[] = ['phone' => $phone, 'message' => $message];
    }
}
