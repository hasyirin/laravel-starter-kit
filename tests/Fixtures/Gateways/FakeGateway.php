<?php

declare(strict_types=1);

namespace Tests\Fixtures\Gateways;

use App\Domain\Shared\Sms\Contracts\SmsGateway;
use SensitiveParameter;
use Throwable;

class FakeGateway implements SmsGateway
{
    /**
     * @var list<array{phone: string, message: string}>
     */
    public array $sent = [];

    public function __construct(public ?string $id = null, public ?Throwable $failure = null) {}

    public function send(#[SensitiveParameter] string $phone, #[SensitiveParameter] string $message): ?string
    {
        if ($this->failure instanceof Throwable) {
            throw $this->failure;
        }

        $this->sent[] = ['phone' => $phone, 'message' => $message];

        return $this->id;
    }
}
