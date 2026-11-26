<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithLabel;
use App\Domain\Shared\Common\Contracts\Enums\HasLabel;

enum SignInEvent: string implements HasLabel
{
    use EnumExtension, InteractsWithLabel;

    case SignIn = 'sign_in';
    case FailedSignIn = 'failed_sign_in';
    case SignOut = 'sign_out';
    case ImpersonationStarted = 'impersonation_started';
    case ImpersonationStopped = 'impersonation_stopped';

    public function label(): string
    {
        return (string) __("user/sign-in-log.events.$this->value");
    }
}
