<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithColor;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithLabel;
use App\Domain\Shared\Common\Contracts\Enums\HasColor;
use App\Domain\Shared\Common\Contracts\Enums\HasLabel;

enum SignInResult: string implements HasColor, HasLabel
{
    use EnumExtension, InteractsWithColor, InteractsWithLabel;

    case SignedIn = 'signed_in';
    case WrongPassword = 'wrong_password';
    case NoMatchingUser = 'no_matching_user';
    case WrongTwoFactorCode = 'wrong_two_factor_code';
    case PasskeyFailed = 'passkey_failed';
    case LockedOut = 'locked_out';

    public function label(): string
    {
        return (string) __("user/sign-in-log.results.$this->value");
    }

    public function color(): string
    {
        return match ($this) {
            self::SignedIn => 'green',
            self::WrongPassword, self::NoMatchingUser, self::WrongTwoFactorCode, self::PasskeyFailed, self::LockedOut => 'red',
        };
    }
}
