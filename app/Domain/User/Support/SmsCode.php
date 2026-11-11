<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use SensitiveParameter;

final readonly class SmsCode
{
    public const int MINUTES = 10;

    public const int TRIES = 5;

    private function __construct(#[SensitiveParameter] private string $key) {}

    public static function forVerification(#[SensitiveParameter] string $phone): self
    {
        return new self("phone-verification:{$phone}");
    }

    public static function forPasswordReset(#[SensitiveParameter] string $phone): self
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

        // The tries go in first, so they never expire before the code.
        cache()->put($this->triesKey(), 0, $expiresAt);
        cache()->put($this->cacheKey(), $this->hash($code), $expiresAt);

        return $code;
    }

    public function check(#[SensitiveParameter] string $code): bool
    {
        if (! $this->unused()) {
            return false;
        }

        // The try counts before the read, so a code that runs out during this check does not pass.
        $tries = cache()->increment($this->triesKey());

        // Some stores, such as the database store, cannot count a missing key. A code without a count stops working.
        if (! is_int($tries)) {
            $this->forget();

            return false;
        }

        $hash = cache()->get($this->cacheKey());
        $passed = is_string($hash) && $tries <= self::TRIES && hash_equals($hash, $this->hash($code));

        if ($passed || $tries >= self::TRIES) {
            $this->forget();
        }

        return $passed;
    }

    public function forget(): void
    {
        cache()->forget($this->cacheKey());
        cache()->forget($this->triesKey());
    }

    private function cacheKey(): string
    {
        return 'sms-code:'.hash('sha256', $this->key);
    }

    private function triesKey(): string
    {
        return $this->cacheKey().':tries';
    }

    private function hash(#[SensitiveParameter] string $code): string
    {
        return hash_hmac('sha256', $code, config()->string('app.key'));
    }
}
