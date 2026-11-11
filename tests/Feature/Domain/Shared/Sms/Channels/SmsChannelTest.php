<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Enums\MessageFailureReason;
use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Sms\Channels\SmsChannel;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\Shared\Sms\Exceptions\CallingCodeNotAllowed;
use App\Domain\User\Models\User;
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

test('an sms is logged as sent with its channel, its number, and the id from the gateway', function (): void {
    $this->freezeSecond();
    app()->instance(SmsGateway::class, new FakeGateway('sms-1'));
    $user = User::factory()->create(['phone' => '+60123456789']);

    $user->notify(new TextMessage('The office is closed today.'));

    $message = Message::query()->sole();
    expect($message->channel)->toBe('sms')
        ->and($message->status)->toBe(MessageStatus::Sent)
        ->and($message->recipient_address)->toBe('+60123456789')
        ->and($message->provider_id)->toBe('sms-1')
        ->and($message->notifiable->is($user))->toBeTrue()
        ->and($message->sent_at)->toEqual(now());
});

test('an sms to a user without a phone number is logged as failed for that reason', function (): void {
    $user = User::factory()->withoutPhone()->create();

    $user->notify(new TextMessage('The office is closed today.'));

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->failure_reason)->toBe(MessageFailureReason::NoPhoneNumber)
        ->and($this->gateway->sent)->toBeEmpty();
});

test('an sms to a calling code outside the list is not sent and is logged as failed for that reason', function (): void {
    $user = User::factory()->create(['phone' => '+6591234567']);

    expect(fn () => $user->notify(new TextMessage('The office is closed today.')))
        ->toThrow(LogicException::class, 'The app sends SMS only to the calling codes in system.sms.calling_codes.');

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->recipient_address)->toBe('+6591234567')
        ->and($message->failure_reason)->toBe(MessageFailureReason::CallingCodeNotAllowed)
        ->and($this->gateway->sent)->toBeEmpty();
});

test('an sms that the gateway refuses is logged as failed with its error, and never with a mail server reason', function (): void {
    $this->freezeSecond();
    app()->instance(SmsGateway::class, new FakeGateway(failure: new RuntimeException('Connection refused by the provider.')));
    $user = User::factory()->create(['phone' => '+60123456789']);

    expect(fn () => $user->notify(new TextMessage('The office is closed today.')))
        ->toThrow(RuntimeException::class, 'Connection refused by the provider.');

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->recipient_address)->toBe('+60123456789')
        ->and($message->error['message'])->toBe('Connection refused by the provider.')
        ->and($message->failure_reason)->toBe(MessageFailureReason::Unknown)
        ->and($message->failed_at)->toEqual(now());
});

test('an intercepted sms keeps its real number and the number that it went to in the message log', function (): void {
    config(['system.sms.intercept.enabled' => true]);
    $this->actingAs(User::factory()->create(['phone' => '+60111111111']));

    User::factory()->create(['phone' => '+60123456789'])->notify(new TextMessage('The office is closed today.'));

    $message = Message::query()->sole();
    expect($message->recipient_address)->toBe('+60123456789')
        ->and($message->intercepted_to)->toBe('+60111111111')
        ->and($message->status)->toBe(MessageStatus::Sent);
});

test('an sms that the intercept drops is logged as failed', function (): void {
    config(['system.sms.intercept.enabled' => true, 'system.sms.intercept.phone' => '']);

    User::factory()->create(['phone' => '+60123456789'])->notify(new TextMessage('The office is closed today.'));

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->failure_reason)->toBe(MessageFailureReason::Dropped)
        ->and($this->gateway->sent)->toBe([]);
});

test('the channel refuses an intercept number outside the calling codes, and the error names the intercept', function (): void {
    config(['system.sms.intercept.enabled' => true]);
    $this->actingAs(User::factory()->create(['phone' => '+6591234567']));
    $user = User::factory()->create(['phone' => '+60123456789']);

    expect(fn () => $user->notify(new TextMessage('The office is closed today.')))
        ->toThrow(CallingCodeNotAllowed::class, 'The SMS intercept sends SMS only to the calling codes in system.sms.calling_codes.');

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->failure_reason)->toBe(MessageFailureReason::CallingCodeNotAllowed)
        ->and($message->intercepted_to)->toBeNull()
        ->and($this->gateway->sent)->toBe([]);
});

test('with the intercept on, the channel still refuses a real number outside the calling codes', function (): void {
    config(['system.sms.intercept.enabled' => true]);
    $this->actingAs(User::factory()->create(['phone' => '+60111111111']));
    $user = User::factory()->create(['phone' => '+6591234567']);

    expect(fn () => $user->notify(new TextMessage('The office is closed today.')))
        ->toThrow(CallingCodeNotAllowed::class, 'The app sends SMS only to the calling codes in system.sms.calling_codes.');

    expect($this->gateway->sent)->toBe([]);
});
