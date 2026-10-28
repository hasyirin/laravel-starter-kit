<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

trait ConfirmsPassword
{
    protected function passwordIsConfirmed(): bool
    {
        $confirmedAt = session('auth.password_confirmed_at');

        if (is_int($confirmedAt) && time() - $confirmedAt <= config()->integer('auth.password_timeout')) {
            return true;
        }

        session()->put('url.intended', route('security.edit'));

        $this->redirectRoute('password.confirm');

        return false;
    }
}
