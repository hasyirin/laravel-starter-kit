<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Actions\ResendMessage;
use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;
use Tests\Fixtures\Gateways\FakeGateway;
use Tests\Fixtures\Notifications\LetterIssued;
use Tests\Fixtures\Notifications\LetterReady;

test('a resend makes a new row that links to the original', function (): void {
    $user = User::factory()->create(['email' => 'aisyah@example.com']);
    $user->notify(new LetterIssued(cc: ['officer@example.com']));

    $original = Message::query()->sole();

    ResendMessage::make()->execute($original);

    $resent = Message::query()->whereKeyNot($original->id)->sole();
    expect($resent->resend_of_id)->toBe($original->id)
        ->and($resent->recipient_address)->toBe('aisyah@example.com')
        ->and($resent->cc)->toBe([['address' => 'officer@example.com', 'name' => '']])
        ->and($resent->notifiable->is($user))->toBeTrue();
});

test('a resend to a new address changes only the to recipient, and the user is no longer its recipient', function (): void {
    $user = User::factory()->create(['email' => 'aisyah@example.com']);
    $user->notify(new LetterIssued(cc: ['officer@example.com']));

    $original = Message::query()->sole();

    ResendMessage::make()->execute($original, 'aisyah.new@example.com');

    $resent = Message::query()->whereKeyNot($original->id)->sole();
    expect($resent->recipient_address)->toBe('aisyah.new@example.com')
        ->and($resent->cc)->toBe([['address' => 'officer@example.com', 'name' => '']])
        ->and($resent->notifiable)->toBeNull()
        ->and($resent->isRecipient($user))->toBeFalse();
});

test('a resend of a resent email goes to the address of that email', function (): void {
    $user = User::factory()->create(['email' => 'aisyah@example.com']);
    $user->notify(new LetterIssued);
    ResendMessage::make()->execute(Message::query()->sole(), 'aisyah.new@example.com');
    $resent = Message::query()->latest('id')->first();

    ResendMessage::make()->execute($resent);

    expect(Message::query()->latest('id')->first()->recipient_address)->toBe('aisyah.new@example.com');
});

test('a resend after the user changed the email goes to the address of the row', function (): void {
    $user = User::factory()->create(['email' => 'aisyah@example.com']);
    $user->notify(new LetterIssued);
    $user->update(['email' => 'aisyah.changed@example.com']);

    ResendMessage::make()->execute(Message::query()->sole());

    expect(Message::query()->latest('id')->first()->recipient_address)->toBe('aisyah@example.com');
});

test('a resend of an email to an address without an account goes to that address', function (): void {
    Notification::route('mail', 'guest@example.com')->notify(new LetterIssued);
    $original = Message::query()->sole();

    ResendMessage::make()->execute($original);

    expect(Message::query()->whereKeyNot($original->id)->sole()->recipient_address)->toBe('guest@example.com');
});

test('an email with sensitive values cannot be resent', function (): void {
    User::factory()->create()->notify(new LetterIssued(secret: 'secret'));

    ResendMessage::make()->execute(Message::query()->sole());
})->throws(LogicException::class, 'cannot be resent');

test('a resend of an sms makes a new row for the same number that links to the original', function (): void {
    app()->instance(SmsGateway::class, $gateway = new FakeGateway);
    $user = User::factory()->create(['phone' => '+60123456789']);
    $user->notify(new LetterReady);

    $original = Message::query()->sole();

    ResendMessage::make()->execute($original);

    $resent = Message::query()->whereKeyNot($original->id)->sole();
    expect($resent->channel)->toBe('sms')
        ->and($resent->resend_of_id)->toBe($original->id)
        ->and($resent->recipient_address)->toBe('+60123456789')
        ->and($resent->notifiable->is($user))->toBeTrue()
        ->and($gateway->sent)->toHaveCount(2);
});

test('a resend of an sms to another number goes to that number, and the user is no longer its recipient', function (): void {
    app()->instance(SmsGateway::class, $gateway = new FakeGateway);
    User::factory()->create(['phone' => '+60123456789'])->notify(new LetterReady);
    $original = Message::query()->sole();

    ResendMessage::make()->execute($original, '+60111111111');

    $resent = Message::query()->whereKeyNot($original->id)->sole();
    expect($resent->recipient_address)->toBe('+60111111111')
        ->and($resent->notifiable)->toBeNull()
        ->and($gateway->sent[1])->toBe(['phone' => '+60111111111', 'message' => 'Your letter is ready.']);
});

test('a resend of an sms to a number without an account goes to that number', function (): void {
    app()->instance(SmsGateway::class, $gateway = new FakeGateway);
    Notification::route('sms', '+60123456789')->notify(new LetterReady);
    $original = Message::query()->sole();

    ResendMessage::make()->execute($original);

    expect(Message::query()->whereKeyNot($original->id)->sole()->recipient_address)->toBe('+60123456789')
        ->and($gateway->sent)->toHaveCount(2);
});

test('an sms with sensitive values cannot be resent', function (): void {
    app()->instance(SmsGateway::class, new FakeGateway);
    User::factory()->create()->notify(new LetterReady(secret: '4821'));

    ResendMessage::make()->execute(Message::query()->sole());
})->throws(LogicException::class, 'cannot be resent');

test('a message on a channel that an app adds cannot be resent', function (): void {
    Notification::extend('pager', fn (): object => new class
    {
        public function send(object $notifiable, Illuminate\Notifications\Notification $notification): void {}
    });
    User::factory()->create()->notify(new LetterReady(channels: ['pager']));

    ResendMessage::make()->execute(Message::query()->sole());
})->throws(LogicException::class, 'cannot be resent');

test('with the intercept on, a resend of an sms to another number goes to the signed-in user', function (): void {
    config(['system.sms.intercept.enabled' => true]);
    app()->instance(SmsGateway::class, $gateway = new FakeGateway);
    $this->actingAs(User::factory()->create(['phone' => '+60111111111']));
    User::factory()->create(['phone' => '+60123456789'])->notify(new LetterReady);

    ResendMessage::make()->execute(Message::query()->sole(), '+60122222222');

    expect(collect($gateway->sent)->pluck('phone')->all())->toBe(['+60111111111', '+60111111111']);
});

test('a message that is still on its way cannot be resent', function (): void {
    User::factory()->create()->notify(new LetterIssued);
    $message = Message::query()->sole();
    $message->update(['status' => MessageStatus::Sending]);

    ResendMessage::make()->execute($message);
})->throws(LogicException::class, 'is not on its way');
