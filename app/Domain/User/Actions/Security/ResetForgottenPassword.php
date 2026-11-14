<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Security;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Concerns\PasswordValidationRules;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use SensitiveParameter;

class ResetForgottenPassword implements ResetsUserPasswords
{
    use Actionable;
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, #[SensitiveParameter] array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
