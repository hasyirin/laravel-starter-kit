<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Support;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\MailboxListHeader;

/**
 * Reads the recipients that an email was meant for. InterceptMail moves them into
 * X-Intercepted-To, X-Intercepted-Cc, and X-Intercepted-Bcc headers.
 */
final class Recipients
{
    public const array FIELDS = ['To', 'Cc', 'Bcc'];

    /**
     * @return array<int, Address>
     */
    public static function to(Email $message): array
    {
        return self::intended($message, 'To');
    }

    /**
     * @return array<int, Address>
     */
    public static function cc(Email $message): array
    {
        return self::intended($message, 'Cc');
    }

    /**
     * @return array<int, Address>
     */
    public static function bcc(Email $message): array
    {
        return self::intended($message, 'Bcc');
    }

    public static function intercepted(Email $message): bool
    {
        return collect(self::FIELDS)->contains(fn (string $field): bool => $message->getHeaders()->has(self::header($field)));
    }

    public static function header(string $field): string
    {
        return "X-Intercepted-$field";
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array{address: string, name: string}>|null
     */
    public static function toArray(array $addresses): ?array
    {
        if ($addresses === []) {
            return null;
        }

        return collect($addresses)
            ->map(fn (Address $address): array => ['address' => $address->getAddress(), 'name' => $address->getName()])
            ->values()
            ->all();
    }

    /**
     * @return array<int, Address>
     */
    private static function intended(Email $message, string $field): array
    {
        if (! self::intercepted($message)) {
            return collect(match ($field) {
                'To' => $message->getTo(),
                'Cc' => $message->getCc(),
                default => $message->getBcc(),
            })->values()->all();
        }

        $header = $message->getHeaders()->get(self::header($field));

        if (! $header instanceof MailboxListHeader) {
            return [];
        }

        return collect($header->getAddresses())->values()->all();
    }
}
