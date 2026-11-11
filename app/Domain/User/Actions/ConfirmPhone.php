<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use App\Domain\User\Support\SmsCode;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class ConfirmPhone
{
    use Actionable;

    /**
     * @throws ValidationException
     */
    public function execute(User $user, #[SensitiveParameter] string $code): void
    {
        $phone = $user->phone;

        if ($phone === null || ! SmsCode::forVerification($phone)->check($code)) {
            throw $this->invalid();
        }

        $user->getConnection()->transaction(function () use ($user, $phone): void {
            $user->newQueryWithoutRelationships()->whereKey($user->getKey())->lockForUpdate()->first();
            $user->refresh();

            throw_if($user->phone !== $phone, $this->invalid());

            $user->markPhoneAsVerified();
        });
    }

    private function invalid(): ValidationException
    {
        return ValidationException::withMessages(['code' => __('user/phone.code_invalid')]);
    }
}
