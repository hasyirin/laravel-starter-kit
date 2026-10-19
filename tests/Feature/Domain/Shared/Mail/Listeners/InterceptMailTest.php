<?php

use App\Domain\User\Models\User;
use Illuminate\Mail\Message;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

function sendInterceptTestMail(): void
{
    Mail::raw('Body', fn (Message $message): Message => $message->to('to@example.com')->cc('cc@example.com')->bcc('bcc@example.com')->subject('Hello'));
}

/**
 * @return Collection<int, Email>
 */
function sentInterceptTestMails(): Collection
{
    return Mail::mailer('array')->getSymfonyTransport()->messages()->map(fn (SentMessage|Symfony\Component\Mailer\SentMessage $sent): Email => $sent->getOriginalMessage());
}

/**
 * @param  list<Address>  $addresses
 * @return list<string>
 */
function interceptAddresses(array $addresses): array
{
    return array_map(fn (Address $address): string => $address->getAddress(), $addresses);
}

beforeEach(function (): void {
    config(['mail.intercept.enabled' => true, 'mail.intercept.address' => '']);
});

test('emails go to their real recipients when the intercept is off', function (): void {
    config(['mail.intercept.enabled' => false]);

    $this->actingAs(User::factory()->create());
    sendInterceptTestMail();

    expect(interceptAddresses(sentInterceptTestMails()->sole()->getTo()))->toBe(['to@example.com']);
});

test('emails go to the current user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    sendInterceptTestMail();

    $email = sentInterceptTestMails()->sole();

    expect(interceptAddresses($email->getTo()))->toBe([$user->email])
        ->and($email->getCc())->toBeEmpty()
        ->and($email->getBcc())->toBeEmpty()
        ->and($email->getHeaders()->getHeaderBody('X-Intercepted-To'))->toBe('to@example.com, cc@example.com, bcc@example.com');
});

test('emails go to the user who was logged in when they were queued', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    auth()->forgetGuards();
    sendInterceptTestMail();

    expect(auth()->user())->toBeNull()
        ->and(interceptAddresses(sentInterceptTestMails()->sole()->getTo()))->toBe([$user->email]);
});

test('queued emails go to the user who queued them even when that user is deleted', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    auth()->forgetGuards();
    $user->delete();
    sendInterceptTestMail();

    expect(interceptAddresses(sentInterceptTestMails()->sole()->getTo()))->toBe([$user->email]);
});

test('emails go to the fallback address when there is no current user', function (): void {
    config(['mail.intercept.address' => 'fallback@example.com']);

    sendInterceptTestMail();

    expect(interceptAddresses(sentInterceptTestMails()->sole()->getTo()))->toBe(['fallback@example.com']);
});

test('emails are dropped when there is no current user and no fallback address', function (): void {
    Log::spy();

    sendInterceptTestMail();

    expect(sentInterceptTestMails())->toBeEmpty();

    Log::shouldHaveReceived('info')->once()->withArgs(fn (string $message, array $context): bool => $context === ['to' => 'to@example.com, cc@example.com, bcc@example.com', 'subject' => 'Hello']);
});

test('the intercept is off in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->actingAs(User::factory()->create());
    sendInterceptTestMail();

    expect(interceptAddresses(sentInterceptTestMails()->sole()->getTo()))->toBe(['to@example.com']);
});
