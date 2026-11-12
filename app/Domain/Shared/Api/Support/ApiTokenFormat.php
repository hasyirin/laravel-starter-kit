<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Support;

use Illuminate\Support\Str;
use SensitiveParameter;

class ApiTokenFormat
{
    private const string ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private const int RANDOM_LENGTH = 40;

    private const int CHECKSUM_LENGTH = 6;

    public static function generate(): string
    {
        $random = Str::random(self::RANDOM_LENGTH);

        return self::prefix().$random.self::checksum($random);
    }

    public static function valid(#[SensitiveParameter] string $token): bool
    {
        $body = str($token)->after(self::prefix());

        if (! str_starts_with($token, self::prefix()) || ! $body->isMatch('/\A[0-9A-Za-z]{'.(self::RANDOM_LENGTH + self::CHECKSUM_LENGTH).'}\z/')) {
            return false;
        }

        return hash_equals(self::checksum($body->substr(0, self::RANDOM_LENGTH)->value()), $body->substr(self::RANDOM_LENGTH)->value());
    }

    public static function hash(#[SensitiveParameter] string $token): string
    {
        return hash('sha256', $token);
    }

    public static function lastFour(#[SensitiveParameter] string $token): string
    {
        return substr($token, -4);
    }

    private static function prefix(): string
    {
        return config()->string('system.api.token_prefix').'_';
    }

    private static function checksum(#[SensitiveParameter] string $random): string
    {
        $number = crc32($random);
        $checksum = '';

        do {
            $checksum = self::ALPHABET[$number % 62].$checksum;
            $number = intdiv($number, 62);
        } while ($number > 0);

        return str_pad($checksum, self::CHECKSUM_LENGTH, '0', STR_PAD_LEFT);
    }
}
