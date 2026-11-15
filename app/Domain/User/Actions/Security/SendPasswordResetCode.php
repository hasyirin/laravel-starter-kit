<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Security;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Sms\Support\CallingCodes;
use App\Domain\User\Exceptions\SmsCodeThrottled;
use App\Domain\User\Exceptions\SmsPasswordResetLocked;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PasswordResetCodeSms;
use App\Domain\User\Support\SmsCode;
use App\Domain\User\Support\SmsCodeLimit;
use App\Domain\User\Support\SmsPasswordResetLock;
use SensitiveParameter;

class SendPasswordResetCode
{
    use Actionable;

    /**
     * @throws SmsPasswordResetLocked
     * @throws SmsCodeThrottled
     */
    public function execute(#[SensitiveParameter] string $phone, string $ip): void
    {
        SmsPasswordResetLock::ensureOpen($phone);
        SmsCodeLimit::attempt("password-reset:phone:{$phone}", "password-reset:ip:{$ip}");

        if (! CallingCodes::allows($phone)) {
            return;
        }

        // The code goes out after the response, so the response takes the same time for every number.
        defer(function () use ($phone): void {
            $this->send($phone);
        });
    }

    private function send(#[SensitiveParameter] string $phone): void
    {
        $user = User::query()->where('phone', $phone)->whereNotNull('phone_verified_at')->first();

        if (! $user instanceof User) {
            return;
        }

        $user->notify(new PasswordResetCodeSms(SmsCode::forPasswordReset($phone)->issue()));
    }
}
