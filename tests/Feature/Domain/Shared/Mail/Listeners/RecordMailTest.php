<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\Shared\Media\Enums\MediaCollection;
use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\User\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email;
use Tests\Fixtures\Notifications\LetterIssued;

/**
 * @return Collection<int, Email>
 */
function sentLogTestMails(): Collection
{
    return Mail::mailer('array')->getSymfonyTransport()->messages()
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

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Sent)
        ->and($message->recipient_address)->toBe('aisyah@example.com')
        ->and($message->recipient_name)->toBeNull()
        ->and($message->subject)->toBe('Letter issued')
        ->and($message->notifiable->is($user))->toBeTrue()
        ->and($message->resource->is($letter))->toBeTrue()
        ->and($message->provider_id)->not->toBeNull()
        ->and($message->sent_at)->toEqual(now());
});

test('the log hides sensitive values in every form and saves no payload for them', function (): void {
    $user = User::factory()->create();

    $user->notify(new LetterIssued(secret: 'a&b<secret>'));

    $message = Message::query()->sole();
    expect($message->html_body)->not->toContain('a&amp;b&lt;secret&gt;')
        ->and($message->html_body)->toContain('[hidden]')
        ->and($message->text_body)->not->toContain('a&b<secret>')
        ->and($message->text_body)->toContain('[hidden]')
        ->and($message->subject)->toBe('Letter issued with code [hidden]')
        ->and($message->payload)->toBeNull()
        ->and($message->resendable)->toBeFalse();
});

test('the log keeps the cc and bcc recipients', function (): void {
    $user = User::factory()->create();

    $user->notify(new LetterIssued(cc: ['officer@example.com'], bcc: ['archive@example.com']));

    $message = Message::query()->sole();
    expect($message->cc)->toBe([['address' => 'officer@example.com', 'name' => '']])
        ->and($message->bcc)->toBe([['address' => 'archive@example.com', 'name' => '']]);
});

test('a notification email with more than one to recipient is not sent, and its row keeps the first recipient', function (): void {
    expect(fn () => Notification::route('mail', ['one@example.com', 'two@example.com'])->notify(new LetterIssued))
        ->toThrow(LogicException::class, 'exactly one To recipient');

    $message = Message::query()->sole();
    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->recipient_address)->toBe('one@example.com')
        ->and($message->subject)->toBe('Letter issued')
        ->and(sentLogTestMails())->toBeEmpty();
});

test('a notification without the message log contract sends one row to each to recipient', function (): void {
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

    $messages = Message::query()->orderBy('recipient_address')->get();
    expect($messages->pluck('recipient_address')->all())->toBe(['one@example.com', 'two@example.com'])
        ->and($messages->pluck('status')->unique()->all())->toBe([MessageStatus::Sent]);
});

test('the log keeps its own copy of each attachment', function (): void {
    Storage::fake('media');
    $user = User::factory()->create();

    $user->notify(new LetterIssued(attached: true));

    $media = Message::query()->sole()->getMedia(MediaCollection::Attachments)->sole();
    expect($media->file_name)->toBe('letter.pdf')
        ->and(file_get_contents($media->getPath()))->toBe('letter content');
});

test('tracking wraps the web links of the email and adds a pixel', function (): void {
    $user = User::factory()->create();

    $user->notify(new LetterIssued);

    $message = Message::query()->with('links')->sole();
    $email = sentLogTestMails()->sole();
    $links = $message->links->keyBy('url');
    expect($links->keys()->all())->toContain('https://example.org/guide?a=1&b=2', route('settings'))
        ->and($links[route('settings')]->requires_auth)->toBeTrue()
        ->and($links['https://example.org/guide?a=1&b=2']->requires_auth)->toBeFalse()
        ->and($email->getHtmlBody())->toContain(e(route('mail.link', $links[route('settings')]->token)))
        ->and($email->getHtmlBody())->toContain('href="mailto:help@example.com"')
        ->and($email->getHtmlBody())->not->toContain('https://example.org/guide')
        ->and($email->getHtmlBody())->toContain(e(route('mail.open', $message->tracking_token)))
        ->and($email->getTextBody())->toContain(route('mail.link', $links[route('settings')]->token))
        ->and($message->html_body)->toContain('https://example.org/guide?a=1&amp;b=2');
});

test('an email without tracking keeps its links and gets no pixel', function (): void {
    $user = User::factory()->create();

    $user->notify(new LetterIssued(tracking: false));

    $message = Message::query()->sole();
    $this->assertDatabaseEmpty(MailLink::class);
    expect(sentLogTestMails()->sole()->getHtmlBody())->not->toContain(route('mail.open', $message->tracking_token));
});

test('an email sent without a notification is logged as sent without its body', function (): void {
    Mail::raw('Body', fn (Illuminate\Mail\Message $email): Illuminate\Mail\Message => $email->to('a@example.com')->to('b@example.com')->subject('Plain'));

    $messages = Message::query()->orderBy('recipient_address')->get();
    expect($messages->pluck('recipient_address')->all())->toBe(['a@example.com', 'b@example.com'])
        ->and($messages->pluck('status')->unique()->all())->toBe([MessageStatus::Sent])
        ->and($messages->pluck('html_body')->filter()->all())->toBeEmpty()
        ->and($messages->pluck('payload')->filter()->all())->toBeEmpty();
});

test('an intercepted email keeps its intended recipients and the address it went to', function (): void {
    config(['mail.intercept.enabled' => true]);
    $developer = User::factory()->create(['email' => 'dev@example.com']);
    $user = User::factory()->create(['email' => 'aisyah@example.com']);
    $this->actingAs($developer);

    $user->notify(new LetterIssued(cc: ['officer@example.com']));

    $message = Message::query()->sole();
    expect($message->recipient_address)->toBe('aisyah@example.com')
        ->and($message->cc)->toBe([['address' => 'officer@example.com', 'name' => '']])
        ->and($message->intercepted_to)->toBe('dev@example.com');
});

test('an email without a to recipient is logged without a recipient', function (): void {
    Mail::raw('Body', fn (Illuminate\Mail\Message $email): Illuminate\Mail\Message => $email->bcc('archive@example.com')->subject('Copy only'));

    $message = Message::query()->sole();
    expect($message->recipient_address)->toBeNull()
        ->and($message->bcc)->toBe([['address' => 'archive@example.com', 'name' => '']])
        ->and($message->status)->toBe(MessageStatus::Sent);
});

test('a notification without the message log contract and without a to recipient is sent and logged', function (): void {
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

    $message = Message::query()->sole();
    expect($message->recipient_address)->toBeNull()
        ->and($message->status)->toBe(MessageStatus::Sent)
        ->and(sentLogTestMails())->toHaveCount(1);
});
