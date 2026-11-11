<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithLabel;
use App\Domain\Shared\Common\Contracts\Enums\HasLabel;
use App\Domain\Shared\Sms\Exceptions\CallingCodeNotAllowed;

enum MessageFailureReason: string implements HasLabel
{
    use EnumExtension, InteractsWithLabel;

    case Timeout = 'timeout';
    case InvalidRecipient = 'invalid_recipient';
    case MailboxFull = 'mailbox_full';
    case Rejected = 'rejected';
    case TemporarilyRejected = 'temporarily_rejected';
    case AttachmentTooLarge = 'attachment_too_large';
    case AttachmentMissing = 'attachment_missing';
    case ConnectionFailed = 'connection_failed';
    case AuthenticationFailed = 'authentication_failed';
    case Dropped = 'dropped';
    case NoAddress = 'no_address';
    case NoPhoneNumber = 'no_phone_number';
    case CallingCodeNotAllowed = 'calling_code_not_allowed';
    case Unknown = 'unknown';

    public function label(): string
    {
        return (string) __("message/messages.reasons.$this->value.label");
    }

    public function suggestion(): string
    {
        return (string) __("message/messages.reasons.$this->value.suggestion");
    }

    /**
     * @param  array{type?: string, code?: int|string, message?: string}|null  $error
     */
    public static function fromError(?array $error, string $channel): ?self
    {
        if (blank($error)) {
            return null;
        }

        $type = $error['type'] ?? '';
        $message = str($error['message'] ?? '')->lower();

        $reason = self::tryFrom($type);

        if ($reason instanceof self) {
            return $reason;
        }

        if ($type === CallingCodeNotAllowed::class) {
            return self::CallingCodeNotAllowed;
        }

        if (str_contains($type, 'TimeoutExceededException') || $message->contains('has timed out')) {
            return self::Timeout;
        }

        // The other reasons come from the text of a mail server.
        if ($channel !== 'mail') {
            return self::Unknown;
        }

        return match (true) {
            $message->contains(['unable to open path', 'unable to read file', 'file does not exist', 'no such file']) => self::AttachmentMissing,
            $message->contains(['user unknown', 'no such user', 'recipient address rejected', 'unrouteable', 'address not found', '550 5.1.1', 'invalid address', 'mailbox unavailable']) => self::InvalidRecipient,
            $message->contains(['mailbox full', 'over quota', 'quota exceeded', 'insufficient system storage']) => self::MailboxFull,
            $message->contains(['message too large', 'size exceeds', 'exceeds size limit', 'message size']) => self::AttachmentTooLarge,
            $message->contains(['failed to authenticate', 'authentication failed', 'authentication unsuccessful', 'must issue a starttls', 'username and password not accepted', '535 5.7']) => self::AuthenticationFailed,
            $message->contains(['temporarily rejected', 'try again later', 'temporary failure', '4.3.0', '451', '421']) => self::TemporarilyRejected,
            $message->contains(['connection could not be established', 'connection refused', 'connection timed out', 'could not connect', 'name or service not known', 'network is unreachable', 'ssl operation failed']) => self::ConnectionFailed,
            $message->contains(['spam', 'blocked', 'blacklist', 'rejected', 'policy', '554']) => self::Rejected,
            default => self::Unknown,
        };
    }
}
