<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Domain\User\Actions\SignInLog\RecordSignInEntry;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Laravel\Fortify\Fortify;

class SignInThrottle
{
    /**
     * @param  array<array-key, mixed>  $headers
     */
    public static function login(string $key, array $headers): never
    {
        $email = request()->string(Fortify::username())->lower()->value();
        $user = User::query()->where(Fortify::username(), $email)->first();

        self::record("login|$key", $headers, $user, SignInMethod::Password);

        self::tooManyAttempts($headers);
    }

    /**
     * @param  array<array-key, mixed>  $headers
     */
    public static function twoFactor(array $headers): never
    {
        $user = User::query()->whereKey(request()->session()->get('login.id'))->first();

        self::record("two-factor|{$user?->id}", $headers, $user, SignInMethod::Password, twoFactor: true);

        self::tooManyAttempts($headers);
    }

    /**
     * @param  array<array-key, mixed>  $headers
     */
    public static function passkeys(string $key, array $headers): never
    {
        if (! request()->routeIs('passkey.login', 'passkey.login-options')) {
            self::tooManyAttempts($headers);
        }

        $user = VerifyPasskey::user(request()->string('credential.id')->value());

        self::record("passkeys|$key", $headers, $user, SignInMethod::Passkey);

        self::tooManyAttempts($headers);
    }

    /**
     * @param  array<array-key, mixed>  $headers
     */
    private static function record(string $key, array $headers, ?User $user, SignInMethod $method, bool $twoFactor = false): void
    {
        $retryAfter = max(1, (int) ($headers['Retry-After'] ?? 0));
        $windowEnd = (int) ($headers['X-RateLimit-Reset'] ?? 0);

        if (! cache()->add('sign-in-throttle:'.md5("$key|$windowEnd"), true, $retryAfter)) {
            return;
        }

        RecordSignInEntry::make()->execute(SignInEvent::FailedSignIn, SignInResult::LockedOut, $user, $method, $twoFactor);
    }

    /**
     * @param  array<array-key, mixed>  $headers
     */
    private static function tooManyAttempts(array $headers): never
    {
        throw new ThrottleRequestsException('Too Many Attempts.', null, $headers);
    }
}
