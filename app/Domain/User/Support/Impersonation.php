<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Domain\User\Models\User;

class Impersonation
{
    private const string IMPERSONATOR_KEY = 'impersonation.impersonator_id';

    private const string START_URL_KEY = 'impersonation.start_url';

    public static function active(): bool
    {
        return session()->has(self::IMPERSONATOR_KEY);
    }

    public static function allows(User $impersonator, User $user): bool
    {
        return ! self::forbids($impersonator, $user) && $impersonator->can('impersonate', $user);
    }

    public static function forbids(User $impersonator, User $user): bool
    {
        return $impersonator->is($user) || $user->trashed() || self::active();
    }

    public static function impersonatorId(): ?int
    {
        $id = session(self::IMPERSONATOR_KEY);

        return is_int($id) ? $id : null;
    }

    public static function impersonator(): ?User
    {
        $id = self::impersonatorId();

        if ($id === null) {
            return null;
        }

        return User::query()->find($id);
    }

    public static function startUrl(): ?string
    {
        $url = session(self::START_URL_KEY);

        return is_string($url) ? $url : null;
    }

    public static function begin(User $impersonator, string $startUrl): void
    {
        session()->put([
            self::IMPERSONATOR_KEY => $impersonator->id,
            self::START_URL_KEY => $startUrl,
        ]);
    }

    public static function end(): void
    {
        session()->forget([self::IMPERSONATOR_KEY, self::START_URL_KEY]);
    }
}
