<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Message\Support\CurrentMessage;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\User\Models\User;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\Fixtures\Gateways\FakeGateway;
use Tests\Fixtures\Notifications\LetterIssued;
use Tests\Fixtures\Notifications\LetterReady;
use Tests\Fixtures\Notifications\TextMessage;

beforeEach(function (): void {
    config(['mail.intercept.enabled' => false]);
});

test('the log keeps the text of an sms and hides its sensitive values', function (): void {
    app()->instance(SmsGateway::class, $gateway = new FakeGateway);
    $user = User::factory()->create();

    $user->notify(new LetterReady(secret: '4821'));

    $message = Message::query()->sole();
    expect($message->text_body)->toBe('Your letter is ready. Collect it with code [hidden].')
        ->and($message->payload)->toBeNull()
        ->and($gateway->sent[0]['message'])->toBe('Your letter is ready. Collect it with code 4821.');
});

test('an sms from a class without the message log contract is logged without its text', function (): void {
    app()->instance(SmsGateway::class, new FakeGateway);
    $user = User::factory()->create(['phone' => '+60123456789']);

    $user->notify(new TextMessage('Your code is 123456.'));

    $message = Message::query()->sole();
    expect($message->text_body)->toBeNull()
        ->and($message->recipient_address)->toBe('+60123456789')
        ->and($message->status)->toBe(MessageStatus::Sent);
});

test('a channel that an app adds can give its address, its text, and its id to the log', function (): void {
    Notification::extend('pager', fn (): object => new class
    {
        public function send(object $notifiable, Illuminate\Notifications\Notification $notification): void
        {
            resolve(CurrentMessage::class)->record('pager-17', 'Your letter is ready.');
            resolve(CurrentMessage::class)->sent('page-1');
        }
    });

    User::factory()->create()->notify(new LetterReady(channels: ['pager']));

    $message = Message::query()->sole();
    expect($message->recipient_address)->toBe('pager-17')
        ->and($message->text_body)->toBe('Your letter is ready.')
        ->and($message->provider_id)->toBe('page-1')
        ->and($message->status)->toBe(MessageStatus::Sent);
});

test('a message that is sent while another message is in flight does not take its row', function (): void {
    app()->instance(SmsGateway::class, new FakeGateway);
    $officer = User::factory()->create(['phone' => '+60123456789']);
    $user = User::factory()->create(['email' => 'aisyah@example.com']);
    Event::listen(MessageSending::class, fn () => $officer->notify(new TextMessage('A letter is on its way.')));

    $user->notify(new LetterIssued);

    $messages = Message::query()->orderBy('channel')->get();
    expect($messages->pluck('channel')->all())->toBe(['mail', 'sms'])
        ->and($messages->pluck('status')->unique()->all())->toBe([MessageStatus::Sent])
        ->and($messages[0]->recipient_address)->toBe('aisyah@example.com');
});
