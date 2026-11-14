<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Security;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Exceptions\SmsPasswordResetLocked;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\SmsPasswordResetStarted;
use App\Domain\User\Support\SmsCode;
use App\Domain\User\Support\SmsPasswordResetLock;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class ConfirmPasswordResetCode
{
    use Actionable;

    /**
     * @return array{email: string, token: string}
     *
     * @throws SmsPasswordResetLocked
     * @throws ValidationException
     */
    public function execute(#[SensitiveParameter] string $phone, #[SensitiveParameter] string $code): array
    {
        SmsPasswordResetLock::ensureOpen($phone);

        $user = User::query()->where('phone', $phone)->whereNotNull('phone_verified_at')->first();

        if (! SmsCode::forPasswordReset($phone)->check($code) || ! $user instanceof User) {
            SmsPasswordResetLock::countWrongCode($phone);

            throw ValidationException::withMessages(['code' => __('user/phone.code_invalid')]);
        }

        /** @var PasswordBroker $broker */
        $broker = Password::broker(config()->string('fortify.passwords'));
        $token = $broker->createToken($user);

        $user->notify(new SmsPasswordResetStarted);

        return ['email' => $user->email, 'token' => $token];
    }
}
