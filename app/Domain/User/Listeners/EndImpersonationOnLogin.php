<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Support\Impersonation;
use Illuminate\Auth\Events\Login;

class EndImpersonationOnLogin
{
    public function handle(Login $event): void
    {
        Impersonation::end();
    }
}
