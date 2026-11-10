<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\SmsPasswordResetStarted;
use App\Domain\User\Support\SmsCode;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ConfirmPasswordResetCode
{
    use Actionable;

    /**
     * @return array{email: string, token: string}
     *
     * @throws ValidationException
     */
    public function execute(string $phone, string $code): array
    {
        $user = User::query()->where('phone', $phone)->whereNotNull('phone_verified_at')->first();

        if (! SmsCode::forPasswordReset($phone)->check($code) || ! $user instanceof User) {
            throw ValidationException::withMessages(['code' => __('user/phone.code_invalid')]);
        }

        /** @var PasswordBroker $broker */
        $broker = Password::broker(config()->string('fortify.passwords'));
        $token = $broker->createToken($user);

        $user->notify(new SmsPasswordResetStarted);

        return ['email' => $user->email, 'token' => $token];
    }
}
