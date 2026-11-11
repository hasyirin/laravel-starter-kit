<?php

declare(strict_types=1);

use App\Domain\Shared\Sms\Channels\SmsChannel;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use Illuminate\Notifications\AnonymousNotifiable;
use Tests\Fixtures\Gateways\FakeGateway;
use Tests\Fixtures\Notifications\TextMessage;

beforeEach(function (): void {
    app()->instance(SmsGateway::class, $this->gateway = new FakeGateway);
});

test('the channel sends the text of the notification to the number of the notifiable', function (): void {
    resolve(SmsChannel::class)->send(new AnonymousNotifiable()->route('sms', '+60123456789'), new TextMessage('Your code is 123456.'));

    expect($this->gateway->sent)->toBe([['phone' => '+60123456789', 'message' => 'Your code is 123456.']]);
});

test('the channel refuses a number outside the calling codes', function (): void {
    expect(fn () => resolve(SmsChannel::class)->send(new AnonymousNotifiable()->route('sms', '+6591234567'), new TextMessage('Hi')))
        ->toThrow(LogicException::class, 'The app sends SMS only to the calling codes in system.sms.calling_codes.');

    expect($this->gateway->sent)->toBe([]);
});

test('the channel sends nothing to a notifiable without a number', function (): void {
    resolve(SmsChannel::class)->send(new AnonymousNotifiable, new TextMessage('Hi'));

    expect($this->gateway->sent)->toBe([]);
});
