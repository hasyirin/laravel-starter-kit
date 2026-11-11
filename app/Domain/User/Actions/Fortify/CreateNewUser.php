<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Fortify;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Concerns\PasswordValidationRules;
use App\Domain\User\Concerns\UserInformationValidationRules;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use SensitiveParameter;

class CreateNewUser implements CreatesNewUsers
{
    use Actionable;
    use PasswordValidationRules, UserInformationValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(#[SensitiveParameter] array $input): User
    {
        Validator::make($input, [
            ...$this->userInformationRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'password' => $input['password'],
        ]);
    }
}
