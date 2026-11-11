<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Enums\MailFailureReason;
use Illuminate\Queue\TimeoutExceededException;
use Symfony\Component\Mailer\Exception\TransportException;

test('an error gets the reason that its message shows', function (string $type, string $message, MailFailureReason $reason): void {
    expect(MailFailureReason::fromError(['type' => $type, 'code' => 0, 'message' => $message]))->toBe($reason);
})->with([
    'a timeout' => [TimeoutExceededException::class, 'The job has timed out.', MailFailureReason::Timeout],
    'a missing attachment' => [TransportException::class, 'Unable to open path "/tmp/letter.pdf".', MailFailureReason::AttachmentMissing],
    'an invalid recipient' => [TransportException::class, '550 5.1.1 User unknown', MailFailureReason::InvalidRecipient],
    'a full mailbox' => [TransportException::class, '552 Mailbox full', MailFailureReason::MailboxFull],
    'a large attachment' => [TransportException::class, '552 Message too large', MailFailureReason::AttachmentTooLarge],
    'a failed login' => [TransportException::class, 'Failed to authenticate on SMTP server', MailFailureReason::AuthenticationFailed],
    'a temporary rejection' => [TransportException::class, '450 Try again later', MailFailureReason::TemporarilyRejected],
    'a failed connection' => [TransportException::class, 'Connection could not be established with host "smtp".', MailFailureReason::ConnectionFailed],
    'a rejection' => [TransportException::class, '554 Message rejected as spam', MailFailureReason::Rejected],
    'a dropped email' => ['dropped', 'A mail listener stopped the email before it was sent.', MailFailureReason::Dropped],
    'no address' => ['no_address', 'The recipient has no email address.', MailFailureReason::NoAddress],
    'another error' => [RuntimeException::class, 'Something else went wrong.', MailFailureReason::Unknown],
]);

test('a row without an error has no reason', function (): void {
    expect(MailFailureReason::fromError(null))->toBeNull();
});
