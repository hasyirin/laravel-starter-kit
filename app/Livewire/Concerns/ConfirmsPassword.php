<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

trait ConfirmsPassword
{
    protected function passwordConfirmed(): bool
    {
        $confirmedAt = session('auth.password_confirmed_at');

        if (is_int($confirmedAt) && now()->unix() - $confirmedAt <= config()->integer('auth.password_timeout')) {
            return true;
        }

        session()->put('url.intended', route('security.edit'));

        $this->redirectRoute('password.confirm');

        return false;
    }
}
