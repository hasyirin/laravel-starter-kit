<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use App\Domain\User\Support\SmsCode;
use Illuminate\Validation\ValidationException;

class ConfirmPhone
{
    use Actionable;

    /**
     * @throws ValidationException
     */
    public function execute(User $user, string $code): void
    {
        if ($user->phone === null || ! SmsCode::forVerification($user->phone)->check($code)) {
            throw ValidationException::withMessages(['code' => __('user/phone.code_invalid')]);
        }

        $user->markPhoneAsVerified();
    }
}
