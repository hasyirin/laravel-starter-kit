<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Enums\MessageFailureReason;
use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\User\Models\User;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\Fixtures\Gateways\FakeGateway;
use Tests\Fixtures\Notifications\BellNotice;
use Tests\Fixtures\Notifications\LetterIssued;
use Tests\Fixtures\Notifications\LetterReady;

beforeEach(function (): void {
    config(['mail.intercept.enabled' => false]);
});

test('a notification that goes by email and by sms gets one row for each channel, and only the email is tracked', function (): void {
    config(['mail.tracking' => true]);
    app()->instance(SmsGateway::class, new FakeGateway);
    $user = User::factory()->create(['email' => 'aisyah@example.com', 'phone' => '+60123456789']);

    $user->notify(new LetterReady(channels: ['mail', 'sms']));

    $messages = Message::query()->orderBy('channel')->get();
    expect($messages->pluck('channel')->all())->toBe(['mail', 'sms'])
        ->and($messages->pluck('recipient_address')->all())->toBe(['aisyah@example.com', '+60123456789'])
        ->and($messages->pluck('status')->unique()->all())->toBe([MessageStatus::Sent])
        ->and($messages->pluck('tracked')->all())->toBe([true, false])
        ->and($messages->pluck('notification_id')->unique())->toHaveCount(1);
});

test('a notification under the bell is not logged', function (string $channel): void {
    $user = User::factory()->create();

    $user->notify(new BellNotice($channel));

    $this->assertDatabaseEmpty(Message::class);
})->with(['database', 'broadcast']);

test('a message on a channel that an app adds is logged as sent, without an address, a text, an id, or a resend', function (): void {
    Notification::extend('pager', fn (): object => new class
    {
        public function send(object $notifiable, Illuminate\Notifications\Notification $notification): string
        {
            return 'accepted';
        }
    });
    $user = User::factory()->create();

    $user->notify(new LetterReady(channels: ['pager']));

    $message = Message::query()->sole();
    expect($message->channel)->toBe('pager')
        ->and($message->status)->toBe(MessageStatus::Sent)
        ->and($message->recipient_address)->toBeNull()
        ->and($message->text_body)->toBeNull()
        ->and($message->provider_id)->toBeNull()
        ->and($message->notifiable->is($user))->toBeTrue()
        ->and($message->resendable)->toBeFalse();
});

test('a channel that reports a failed send without an exception gets a failed row', function (): void {
    Notification::extend('pager', fn (): object => new class
    {
        public function send(object $notifiable, Illuminate\Notifications\Notification $notification): void
        {
            event(new NotificationFailed($notifiable, $notification, 'pager', ['reason' => 'The pager is off.']));
        }
    });

    User::factory()->create()->notify(new LetterReady(channels: ['pager']));

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->failure_reason)->toBe(MessageFailureReason::Unknown);
});

test('the log hides sensitive values in the error of a failed send', function (): void {
    app()->instance(SmsGateway::class, new FakeGateway(failure: new RuntimeException('The provider refused the text: Your letter is ready. Collect it with code 4821.')));
    $user = User::factory()->create();

    expect(fn () => $user->notify(new LetterReady(secret: '4821')))->toThrow(RuntimeException::class);

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->error['message'])->toBe('The provider refused the text: Your letter is ready. Collect it with code [hidden].');
});

test('a notification without sensitive values can be rebuilt from its payload', function (): void {
    $user = User::factory()->create();

    $user->notify(new LetterIssued(cc: ['officer@example.com']));

    $message = Message::query()->sole();
    $notification = $message->notification;
    expect($message->getRawOriginal('payload'))->not->toContain('LetterIssued')
        ->and($notification)->toBeInstanceOf(LetterIssued::class)
        ->and($notification->cc)->toBe(['officer@example.com']);
});

test('a failed send is logged with its error and its reason', function (): void {
    $this->freezeSecond();
    $user = User::factory()->create();
    Event::listen(MessageSending::class, fn (): never => throw new TransportException('Connection could not be established with host "smtp".'));

    expect(fn () => $user->notify(new LetterIssued))->toThrow(TransportException::class);

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->error['message'])->toBe('Connection could not be established with host "smtp".')
        ->and($message->failure_reason)->toBe(MessageFailureReason::ConnectionFailed)
        ->and($message->failed_at)->toEqual(now());
});

test('a failed try in a queue job keeps the row sending with its error', function (): void {
    config(['mail.queue_connection' => 'database']);
    $user = User::factory()->create();
    Event::listen(MessageSending::class, fn (): never => throw new TransportException('Connection could not be established with host "smtp".'));
    $user->notify(new LetterIssued);

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--queue' => 'default']);

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Sending)
        ->and($message->error['message'])->toBe('Connection could not be established with host "smtp".')
        ->and($message->failed_at)->toBeNull();
});

test('a failed try keeps the row sending after a sync job runs inside the send', function (): void {
    config(['mail.queue_connection' => 'database']);
    $user = User::factory()->create();
    Event::listen(MessageSending::class, function (): never {
        dispatch(fn (): null => null)->onConnection('sync');

        throw new TransportException('Connection could not be established with host "smtp".');
    });
    $user->notify(new LetterIssued);

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--queue' => 'default']);

    expect(Message::query()->sole()->status)->toBe(MessageStatus::Sending);
});

test('a failed send inside a job that is not a notification job fails the row at once', function (): void {
    $user = User::factory()->create();
    Event::listen(MessageSending::class, fn (): never => throw new TransportException('Connection could not be established with host "smtp".'));
    dispatch(fn () => Notification::sendNow($user, new LetterIssued))->onConnection('database');

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--queue' => 'default']);

    expect(Message::query()->sole()->status)->toBe(MessageStatus::Failed);
});

test('a notification email with more than one to recipient fails at once in a queue job, without another try', function (): void {
    config(['mail.queue_connection' => 'database']);
    Notification::route('mail', ['a@example.com', 'b@example.com'])->notify(new LetterIssued);

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--queue' => 'default']);

    expect(Message::query()->sole()->status)->toBe(MessageStatus::Failed);
    $this->assertDatabaseEmpty('jobs');
});

test('a failed send outside a queue job fails the row at once', function (): void {
    $user = User::factory()->create();
    Event::listen(MessageSending::class, fn (): never => throw new TransportException('Connection could not be established with host "smtp".'));

    expect(fn () => Notification::sendNow($user, new LetterIssued))->toThrow(TransportException::class);

    expect(Message::query()->sole()->status)->toBe(MessageStatus::Failed);
});

test('an email to a recipient without an address is logged as failed for that reason', function (): void {
    Notification::route('mail', '')->notify(new LetterIssued);

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->failure_reason)->toBe(MessageFailureReason::NoAddress);
});

test('an email that the intercept drops is logged as failed', function (): void {
    config(['mail.intercept.enabled' => true, 'mail.intercept.address' => '']);
    $user = User::factory()->create();

    $user->notify(new LetterIssued);

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->failure_reason)->toBe(MessageFailureReason::Dropped);
});

test('another try of the same notification updates its row', function (): void {
    $user = User::factory()->create();
    $notification = new LetterIssued;
    $notification->id = 'f3b1b8a2-1d8e-4a0e-9a57-2a7c3f6b0c11';

    $tries = 0;
    Event::listen(MessageSending::class, function () use (&$tries): void {
        throw_if(++$tries === 1, TransportException::class, 'Connection could not be established with host "smtp".');
    });

    expect(fn () => Notification::sendNow($user, $notification))->toThrow(TransportException::class);
    Notification::sendNow($user, $notification);

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Sent)
        ->and($message->error)->toBeNull()
        ->and($message->failed_at)->toBeNull();
});

test('a notification that a listener skips is logged as dropped', function (): void {
    Event::listen(NotificationSending::class, fn (): bool => false);

    User::factory()->create()->notify(new LetterIssued);

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->failure_reason)->toBe(MessageFailureReason::Dropped);
});

test('the signed-in user who sends the email becomes its preparer', function (): void {
    $sender = User::factory()->create();
    $user = User::factory()->create();
    $this->actingAs($sender);

    $user->notify(new LetterIssued);

    expect(Message::query()->sole()->preparer?->actor->is($sender))->toBeTrue();
});
