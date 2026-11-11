<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PhoneVerificationCodeSms;
use Tests\Fixtures\Gateways\FakeGateway;

test('the sms names the app, the code, and when it expires', function (): void {
    config(['app.name' => 'Kit']);
    $user = User::factory()->make();
    $notification = new PhoneVerificationCodeSms('123456');

    expect($notification->via($user))->toBe(['sms'])
        ->and($notification->toSms($user))->toBe('Your Kit code is 123456. It expires in 10 minutes.');
});

test('the message log keeps the text of a phone verification sms and hides its code', function (): void {
    config(['app.name' => 'Kit']);
    app()->instance(SmsGateway::class, $gateway = new FakeGateway);
    $user = User::factory()->unverifiedPhone()->create();

    $user->notify(new PhoneVerificationCodeSms('123456'));

    $message = Message::query()->sole();
    expect($message->text_body)->toBe('Your Kit code is [hidden]. It expires in 10 minutes.')
        ->and($message->payload)->toBeNull()
        ->and($gateway->sent[0]['message'])->toBe('Your Kit code is 123456. It expires in 10 minutes.');
});
