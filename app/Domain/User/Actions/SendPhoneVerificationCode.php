<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Sms\Exceptions\SmsThrottled;
use App\Domain\Shared\Sms\Support\SmsLimit;
use App\Domain\User\Models\User;

class SendPhoneVerificationCode
{
    use Actionable;

    /**
     * @throws SmsThrottled
     */
    public function execute(User $user): void
    {
        if ($user->phone === null) {
            return;
        }

        SmsLimit::attempt("phone-verification:{$user->id}");

        $user->sendPhoneVerificationNotification();
    }
}
