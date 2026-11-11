<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Enums\MessageFailureReason;
use Illuminate\Queue\TimeoutExceededException;
use Symfony\Component\Mailer\Exception\TransportException;

test('an error gets the reason that its message shows', function (string $type, string $message, MessageFailureReason $reason): void {
    expect(MessageFailureReason::fromError(['type' => $type, 'code' => 0, 'message' => $message]))->toBe($reason);
})->with([
    'a timeout' => [TimeoutExceededException::class, 'The job has timed out.', MessageFailureReason::Timeout],
    'a missing attachment' => [TransportException::class, 'Unable to open path "/tmp/letter.pdf".', MessageFailureReason::AttachmentMissing],
    'an invalid recipient' => [TransportException::class, '550 5.1.1 User unknown', MessageFailureReason::InvalidRecipient],
    'a full mailbox' => [TransportException::class, '552 Mailbox full', MessageFailureReason::MailboxFull],
    'a large attachment' => [TransportException::class, '552 Message too large', MessageFailureReason::AttachmentTooLarge],
    'a failed login' => [TransportException::class, 'Failed to authenticate on SMTP server', MessageFailureReason::AuthenticationFailed],
    'a temporary rejection' => [TransportException::class, '450 Try again later', MessageFailureReason::TemporarilyRejected],
    'a failed connection' => [TransportException::class, 'Connection could not be established with host "smtp".', MessageFailureReason::ConnectionFailed],
    'a rejection' => [TransportException::class, '554 Message rejected as spam', MessageFailureReason::Rejected],
    'a dropped email' => ['dropped', 'A mail listener stopped the email before it was sent.', MessageFailureReason::Dropped],
    'no address' => ['no_address', 'The recipient has no email address.', MessageFailureReason::NoAddress],
    'another error' => [RuntimeException::class, 'Something else went wrong.', MessageFailureReason::Unknown],
]);

test('a row without an error has no reason', function (): void {
    expect(MessageFailureReason::fromError(null))->toBeNull();
});
