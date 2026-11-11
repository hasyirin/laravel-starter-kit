<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PasswordResetCodeSms;
use Tests\Fixtures\Gateways\FakeGateway;

test('the message log keeps the text of a password reset sms and hides its code', function (): void {
    config(['app.name' => 'Kit']);
    app()->instance(SmsGateway::class, $gateway = new FakeGateway);
    $user = User::factory()->create();

    $user->notify(new PasswordResetCodeSms('654321'));

    $message = Message::query()->sole();
    expect($message->text_body)->toBe('Your Kit password reset code is [hidden]. It expires in 10 minutes.')
        ->and($message->payload)->toBeNull()
        ->and($gateway->sent[0]['message'])->toBe('Your Kit password reset code is 654321. It expires in 10 minutes.');
});
