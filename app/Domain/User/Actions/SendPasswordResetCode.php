<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Sms\Exceptions\SmsThrottled;
use App\Domain\Shared\Sms\Support\CallingCodes;
use App\Domain\Shared\Sms\Support\SmsLimit;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PasswordResetCode;
use App\Domain\User\Support\SmsCode;

class SendPasswordResetCode
{
    use Actionable;

    /**
     * @throws SmsThrottled
     */
    public function execute(string $phone, string $ip): void
    {
        SmsLimit::attempt("password-reset:phone:{$phone}", "password-reset:ip:{$ip}");

        if (! CallingCodes::allows($phone)) {
            return;
        }

        $user = User::query()->where('phone', $phone)->whereNotNull('phone_verified_at')->first();

        if (! $user instanceof User) {
            return;
        }

        $user->notify(new PasswordResetCode(SmsCode::forPasswordReset($phone)->issue()));
    }
}
