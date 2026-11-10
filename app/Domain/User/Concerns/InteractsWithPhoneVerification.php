<?php

declare(strict_types=1);

namespace App\Domain\User\Concerns;

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

    public function getPhoneForVerification(): ?string
    {
        return $this->phone;
    }
}
