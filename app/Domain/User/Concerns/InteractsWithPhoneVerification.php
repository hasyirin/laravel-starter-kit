<?php

declare(strict_types=1);

namespace App\Domain\User\Concerns;

use App\Domain\User\Notifications\PhoneVerificationCodeSms;
use App\Domain\User\Support\SmsCode;

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

        $this->notify(new PhoneVerificationCodeSms(SmsCode::forVerification($phone)->issue()));
    }

    public function getPhoneForVerification(): ?string
    {
        return $this->phone;
    }
}
