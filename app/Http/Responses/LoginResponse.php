<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Domain\User\Models\User;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($this->highestAreaUrl());
    }

    public static function home(User $user): string
    {
        return match (true) {
            $user->can(config()->string('system.permissions.system.access')) => route('system.dashboard'),
            $user->can(config()->string('system.permissions.internal.access')) => route('internal.dashboard'),
            default => Fortify::redirects('login'),
        };
    }

    protected function highestAreaUrl(): string
    {
        /** @var User $user */
        $user = auth()->user();

        return self::home($user);
    }
}
