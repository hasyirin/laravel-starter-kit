<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use Carbon\CarbonImmutable;

final readonly class SmsCode
{
    public const int MINUTES = 10;

    public const int TRIES = 5;

    private function __construct(private string $key) {}

    public static function forVerification(string $phone): self
    {
        return new self("phone-verification:{$phone}");
    }

    public static function forPasswordReset(string $phone): self
    {
        return new self("password-reset:{$phone}");
    }

    public function unused(): bool
    {
        return cache()->has($this->cacheKey());
    }

    public function issue(): string
    {
        $code = str((string) random_int(0, 999_999))->padLeft(6, '0')->value();
        $expiresAt = now()->addMinutes(self::MINUTES);

        cache()->put($this->cacheKey(), [
            'hash' => $this->hash($code),
            'tries' => 0,
            'expires_at' => $expiresAt->getTimestamp(),
        ], $expiresAt);

        return $code;
    }

    public function check(string $code): bool
    {
        /** @var array{hash: string, tries: int, expires_at: int}|null $entry */
        $entry = cache()->get($this->cacheKey());

        if ($entry === null) {
            return false;
        }

        if (hash_equals($entry['hash'], $this->hash($code))) {
            cache()->forget($this->cacheKey());

            return true;
        }

        $tries = $entry['tries'] + 1;

        if ($tries >= self::TRIES) {
            cache()->forget($this->cacheKey());

            return false;
        }

        cache()->put($this->cacheKey(), [...$entry, 'tries' => $tries], CarbonImmutable::createFromTimestamp($entry['expires_at']));

        return false;
    }

    private function cacheKey(): string
    {
        return 'sms-code:'.hash('sha256', $this->key);
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, config()->string('app.key'));
    }
}
