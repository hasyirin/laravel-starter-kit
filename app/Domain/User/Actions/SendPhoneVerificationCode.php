<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Exceptions\SmsCodeThrottled;
use App\Domain\User\Models\User;
use App\Domain\User\Support\SmsCodeLimit;

class SendPhoneVerificationCode
{
    use Actionable;

    /**
     * @throws SmsCodeThrottled
     */
    public function execute(User $user): void
    {
        if ($user->phone === null) {
            return;
        }

        SmsCodeLimit::attempt("phone-verification:{$user->id}");

        $user->sendPhoneVerificationNotification();
    }
}
