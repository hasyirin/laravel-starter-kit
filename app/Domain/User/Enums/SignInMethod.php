<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithLabel;
use App\Domain\Shared\Common\Contracts\Enums\HasLabel;

enum SignInMethod: string implements HasLabel
{
    use EnumExtension, InteractsWithLabel;

    case Password = 'password';
    case Passkey = 'passkey';

    public function label(): string
    {
        return (string) __("user/sign-in-log.methods.$this->value");
    }

    public static function fromRoute(?string $routeName): ?self
    {
        return match ($routeName) {
            'login.store', 'two-factor.login.store', 'register.store' => self::Password,
            'passkey.login' => self::Passkey,
            default => null,
        };
    }
}
