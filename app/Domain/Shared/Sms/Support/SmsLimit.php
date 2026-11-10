<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Support;

use App\Domain\Shared\Sms\Exceptions\SmsThrottled;
use Illuminate\Support\Facades\RateLimiter;

final class SmsLimit
{
    /**
     * @var array<string, array{attempts: int, seconds: int}>
     */
    private const array WINDOWS = [
        'minute' => ['attempts' => 1, 'seconds' => 60],
        'hour' => ['attempts' => 5, 'seconds' => 3600],
    ];

    /**
     * @throws SmsThrottled
     */
    public static function attempt(string ...$keys): void
    {
        $windows = collect($keys)->flatMap(fn (string $key): array => collect(self::WINDOWS)
            ->map(fn (array $window, string $name): array => ['key' => "sms:{$key}:{$name}", ...$window])
            ->values()
            ->all());

        $wait = $windows
            ->filter(fn (array $window): bool => RateLimiter::tooManyAttempts($window['key'], $window['attempts']))
            ->map(fn (array $window): int => RateLimiter::availableIn($window['key']))
            ->max();

        if ($wait !== null) {
            throw new SmsThrottled($wait);
        }

        $windows->each(fn (array $window): int => RateLimiter::hit($window['key'], $window['seconds']));
    }
}
