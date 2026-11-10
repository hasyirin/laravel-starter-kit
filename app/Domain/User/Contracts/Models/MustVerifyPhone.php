<?php

declare(strict_types=1);

namespace App\Domain\User\Contracts\Models;

interface MustVerifyPhone
{
    public function hasVerifiedPhone(): bool;

    public function markPhoneAsVerified(): bool;

    public function getPhoneForVerification(): ?string;
}
