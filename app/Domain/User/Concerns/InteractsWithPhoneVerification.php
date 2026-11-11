<?php

declare(strict_types=1);

namespace App\Domain\User\Concerns;

use App\Domain\User\Notifications\PhoneVerificationCodeSms;
use App\Domain\User\Support\SmsCode;
use Throwable;

trait InteractsWithPhoneVerification
{
    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill(['phone_verified_at' => $this->freshTimestamp()])->save();
    }

    public function sendPhoneVerificationNotification(): void
    {
        $phone = $this->getPhoneForVerification();

        if ($phone === null) {
            return;
        }

        $smsCode = SmsCode::forVerification($phone);

        try {
            $this->notify(new PhoneVerificationCodeSms($smsCode->issue()));
        } catch (Throwable $throwable) {
            $smsCode->forget();

            throw $throwable;
        }
    }

    public function getPhoneForVerification(): ?string
    {
        return $this->phone;
    }
}
