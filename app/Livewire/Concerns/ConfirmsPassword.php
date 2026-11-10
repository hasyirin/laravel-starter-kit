<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Http\Enums\Area;

trait ConfirmsPassword
{
    protected function passwordConfirmed(): bool
    {
        $confirmedAt = session('auth.password_confirmed_at');

        if (is_int($confirmedAt) && now()->unix() - $confirmedAt <= config()->integer('auth.password_timeout')) {
            return true;
        }

        session()->put('url.intended', route(Area::current()->route('security.edit')));

        $this->redirectRoute('password.confirm');

        return false;
    }
}
