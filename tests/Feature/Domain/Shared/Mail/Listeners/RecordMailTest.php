<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Enums\MailFailureReason;
use App\Domain\Shared\Mail\Enums\MailStatus;
use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\Shared\Media\Enums\MediaCollection;
use App\Domain\User\Models\User;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Message;
use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;
use Tests\Fixtures\Notifications\LetterIssued;

/**
 * @return Collection<int, Email>
 */
function sentLogTestMails(): Collection
{
    return Illuminate\Support\Facades\Mail::mailer('array')->getSymfonyTransport()->messages()
        ->map(fn (SentMessage|Symfony\Component\Mailer\SentMessage $sent): Email => $sent->getOriginalMessage());
}

beforeEach(function (): void {
    config(['mail.tracking' => true, 'mail.intercept.enabled' => false]);
});

test('a notification email is logged as sent with its recipient, its record, and its message id', function (): void {
    $this->freezeSecond();
    $user = User::factory()->create(['name' => 'Aisyah', 'email' => 'aisyah@example.com']);
    $letter = User::factory()->create();

    $user->notify(new LetterIssued(letter: $letter));

    $mail = Mail::query()->sole();
    expect($mail->status)->toBe(MailStatus::Sent)
        ->and($mail->recipient_email)->toBe('aisyah@example.com')
        ->and($mail->recipient_name)->toBeNull()
        ->and($mail->subject)->toBe('Letter issued')
        ->and($mail->notifiable->is($user))->toBeTrue()
        ->and($mail->resource->is($letter))->toBeTrue()
        ->and($mail->message_id)->not->toBeNull()
        ->and($mail->sent_at)->toEqual(now());
});

test('the log hides sensitive values in every form and saves no payload for them', function (): void {
    $user = User::factory()->create();

    $user->notify(new LetterIssued(secret: 'a&b<secret>'));

    $mail = Mail::query()->sole();
    expect($mail->html_body)->not->toContain('a&amp;b&lt;secret&gt;')
        ->and($mail->html_body)->toContain('[hidden]')
        ->and($mail->text_body)->not->toContain('a&b<secret>')
        ->and($mail->text_body)->toContain('[hidden]')
        ->and($mail->subject)->toBe('Letter issued with code [hidden]')
        ->and($mail->payload)->toBeNull()
        ->and($mail->resendable)->toBeFalse();
});

test('a notification without sensitive values can be rebuilt from its payload', function (): void {
    $user = User::factory()->create();

    $user->notify(new LetterIssued(cc: ['officer@example.com']));

    $mail = Mail::query()->sole();
    $notification = $mail->notification;
    expect($mail->getRawOriginal('payload'))->not->toContain('LetterIssued')
        ->and($notification)->toBeInstanceOf(LetterIssued::class)
        ->and($notification->cc)->toBe(['officer@example.com']);
});

test('the log keeps the cc and bcc recipients', function (): void {
    $user = User::factory()->create();

    $user->notify(new LetterIssued(cc: ['officer@example.com'], bcc: ['archive@example.com']));

    $mail = Mail::query()->sole();
    expect($mail->cc)->toBe([['address' => 'officer@example.com', 'name' => '']])
        ->and($mail->bcc)->toBe([['address' => 'archive@example.com', 'name' => '']]);
});

test('a notification email with more than one to recipient is not sent, and its row keeps the first recipient', function (): void {
    expect(fn () => Notification::route('mail', ['one@example.com', 'two@example.com'])->notify(new LetterIssued))
        ->toThrow(LogicException::class, 'exactly one To recipient');

    $mail = Mail::query()->sole();
    expect($mail->status)->toBe(MailStatus::Failed)
        ->and($mail->recipient_email)->toBe('one@example.com')
        ->and($mail->subject)->toBe('Letter issued')
        ->and(sentLogTestMails())->toBeEmpty();
});

test('a notification without the mail log contract sends one row to each to recipient', function (): void {
    Notification::route('mail', ['one@example.com', 'two@example.com'])->notify(new class extends Illuminate\Notifications\Notification
    {
        /**
         * @return list<string>
         */
        public function via(object $notifiable): array
        {
            return ['mail'];
        }

        public function toMail(object $notifiable): MailMessage
        {
            return new MailMessage()->subject('Package email')->line('Hello.');
        }
    });

    $mails = Mail::query()->orderBy('recipient_email')->get();
    expect($mails->pluck('recipient_email')->all())->toBe(['one@example.com', 'two@example.com'])
        ->and($mails->pluck('status')->unique()->all())->toBe([MailStatus::Sent]);
});

test('the log keeps its own copy of each attachment', function (): void {
    $user = User::factory()->create();

    $user->notify(new LetterIssued(attached: true));

    $media = Mail::query()->sole()->getMedia(MediaCollection::Attachments)->sole();
    expect($media->file_name)->toBe('letter.pdf')
        ->and(file_get_contents($media->getPath()))->toBe('letter content');
});

test('tracking wraps the web links of the email and adds a pixel', function (): void {
    $user = User::factory()->create();

    $user->notify(new LetterIssued);

    $mail = Mail::query()->with('links')->sole();
    $email = sentLogTestMails()->sole();
    $links = $mail->links->keyBy('url');
    expect($links->keys()->all())->toContain('https://example.org/guide?a=1&b=2', route('settings'))
        ->and($links[route('settings')]->requires_auth)->toBeTrue()
        ->and($links['https://example.org/guide?a=1&b=2']->requires_auth)->toBeFalse()
        ->and($email->getHtmlBody())->toContain(e(route('mail.link', $links[route('settings')]->token)))
        ->and($email->getHtmlBody())->toContain('href="mailto:help@example.com"')
        ->and($email->getHtmlBody())->not->toContain('https://example.org/guide')
        ->and($email->getHtmlBody())->toContain(e(route('mail.open', $mail->tracking_token)))
        ->and($email->getTextBody())->toContain(route('mail.link', $links[route('settings')]->token))
        ->and($mail->html_body)->toContain('https://example.org/guide?a=1&amp;b=2');
});

test('an email without tracking keeps its links and gets no pixel', function (): void {
    $user = User::factory()->create();

    $user->notify(new LetterIssued(tracking: false));

    $mail = Mail::query()->sole();
    $this->assertDatabaseEmpty(MailLink::class);
    expect(sentLogTestMails()->sole()->getHtmlBody())->not->toContain(route('mail.open', $mail->tracking_token));
});

test('a failed send is logged with its error and its reason', function (): void {
    $this->freezeSecond();
    $user = User::factory()->create();
    Event::listen(MessageSending::class, fn (): never => throw new TransportException('Connection could not be established with host "smtp".'));

    expect(fn () => $user->notify(new LetterIssued))->toThrow(TransportException::class);

    $mail = Mail::query()->sole();
    expect($mail->status)->toBe(MailStatus::Failed)
        ->and($mail->error['message'])->toBe('Connection could not be established with host "smtp".')
        ->and($mail->failure_reason)->toBe(MailFailureReason::ConnectionFailed)
        ->and($mail->failed_at)->toEqual(now());
});

test('a failed try in a queue job keeps the row sending with its error', function (): void {
    config(['mail.queue_connection' => 'database']);
    $user = User::factory()->create();
    Event::listen(MessageSending::class, fn (): never => throw new TransportException('Connection could not be established with host "smtp".'));
    $user->notify(new LetterIssued);

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--queue' => 'default']);

    $mail = Mail::query()->sole();
    expect($mail->status)->toBe(MailStatus::Sending)
        ->and($mail->error['message'])->toBe('Connection could not be established with host "smtp".')
        ->and($mail->failed_at)->toBeNull();
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

    expect(Mail::query()->sole()->status)->toBe(MailStatus::Sending);
});

test('a failed send inside a job that is not a notification job fails the row at once', function (): void {
    $user = User::factory()->create();
    Event::listen(MessageSending::class, fn (): never => throw new TransportException('Connection could not be established with host "smtp".'));
    dispatch(fn () => Notification::sendNow($user, new LetterIssued))->onConnection('database');

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--queue' => 'default']);

    expect(Mail::query()->sole()->status)->toBe(MailStatus::Failed);
});

test('a notification email with more than one to recipient fails at once in a queue job, without another try', function (): void {
    config(['mail.queue_connection' => 'database']);
    Notification::route('mail', ['a@example.com', 'b@example.com'])->notify(new LetterIssued);

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--queue' => 'default']);

    expect(Mail::query()->sole()->status)->toBe(MailStatus::Failed);
    $this->assertDatabaseEmpty('jobs');
});

test('a failed send outside a queue job fails the row at once', function (): void {
    $user = User::factory()->create();
    Event::listen(MessageSending::class, fn (): never => throw new TransportException('Connection could not be established with host "smtp".'));

    expect(fn () => Notification::sendNow($user, new LetterIssued))->toThrow(TransportException::class);

    expect(Mail::query()->sole()->status)->toBe(MailStatus::Failed);
});

test('an email to a recipient without an address is logged as failed for that reason', function (): void {
    Notification::route('mail', '')->notify(new LetterIssued);

    $mail = Mail::query()->sole();
    expect($mail->status)->toBe(MailStatus::Failed)
        ->and($mail->failure_reason)->toBe(MailFailureReason::NoAddress);
});

test('an email sent without a notification is logged as sent without its body', function (): void {
    Illuminate\Support\Facades\Mail::raw('Body', fn (Message $message): Message => $message->to('a@example.com')->to('b@example.com')->subject('Plain'));

    $mails = Mail::query()->orderBy('recipient_email')->get();
    expect($mails->pluck('recipient_email')->all())->toBe(['a@example.com', 'b@example.com'])
        ->and($mails->pluck('status')->unique()->all())->toBe([MailStatus::Sent])
        ->and($mails->pluck('html_body')->filter()->all())->toBeEmpty()
        ->and($mails->pluck('payload')->filter()->all())->toBeEmpty();
});

test('an intercepted email keeps its intended recipients and the address it went to', function (): void {
    config(['mail.intercept.enabled' => true]);
    $developer = User::factory()->create(['email' => 'dev@example.com']);
    $user = User::factory()->create(['email' => 'aisyah@example.com']);
    $this->actingAs($developer);

    $user->notify(new LetterIssued(cc: ['officer@example.com']));

    $mail = Mail::query()->sole();
    expect($mail->recipient_email)->toBe('aisyah@example.com')
        ->and($mail->cc)->toBe([['address' => 'officer@example.com', 'name' => '']])
        ->and($mail->intercepted_to)->toBe('dev@example.com');
});

test('an email that the intercept drops is logged as failed', function (): void {
    config(['mail.intercept.enabled' => true, 'mail.intercept.address' => '']);
    $user = User::factory()->create();

    $user->notify(new LetterIssued);

    $mail = Mail::query()->sole();
    expect($mail->status)->toBe(MailStatus::Failed)
        ->and($mail->failure_reason)->toBe(MailFailureReason::Dropped);
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

    $mail = Mail::query()->sole();
    expect($mail->status)->toBe(MailStatus::Sent)
        ->and($mail->error)->toBeNull()
        ->and($mail->failed_at)->toBeNull();
});

test('a notification that a listener skips is logged as dropped', function (): void {
    Event::listen(NotificationSending::class, fn (): bool => false);

    User::factory()->create()->notify(new LetterIssued);

    $mail = Mail::query()->sole();
    expect($mail->status)->toBe(MailStatus::Failed)
        ->and($mail->failure_reason)->toBe(MailFailureReason::Dropped);
});

test('the signed-in user who sends the email becomes its preparer', function (): void {
    $sender = User::factory()->create();
    $user = User::factory()->create();
    $this->actingAs($sender);

    $user->notify(new LetterIssued);

    expect(Mail::query()->sole()->preparer?->actor->is($sender))->toBeTrue();
});

test('an email without a to recipient is logged without a recipient', function (): void {
    Illuminate\Support\Facades\Mail::raw('Body', fn (Message $message): Message => $message->bcc('archive@example.com')->subject('Copy only'));

    $mail = Mail::query()->sole();
    expect($mail->recipient_email)->toBeNull()
        ->and($mail->bcc)->toBe([['address' => 'archive@example.com', 'name' => '']])
        ->and($mail->status)->toBe(MailStatus::Sent);
});

test('a notification without the mail log contract and without a to recipient is sent and logged', function (): void {
    Notification::route('mail', 'unused@example.com')->notify(new class extends Illuminate\Notifications\Notification
    {
        /**
         * @return list<string>
         */
        public function via(object $notifiable): array
        {
            return ['mail'];
        }

        public function toMail(object $notifiable): Mailable
        {
            return new Mailable()->bcc('archive@example.com')->subject('Copy only')->html('<p>Hello.</p>');
        }
    });

    $mail = Mail::query()->sole();
    expect($mail->recipient_email)->toBeNull()
        ->and($mail->status)->toBe(MailStatus::Sent)
        ->and(sentLogTestMails())->toHaveCount(1);
});
