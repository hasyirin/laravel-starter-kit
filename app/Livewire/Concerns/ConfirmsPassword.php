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

        session()->put('url.intended', route(Area::current()->route('settings'), ['tab' => 'security']));

        $this->redirectRoute('password.confirm');

        return false;
    }

    protected function ensurePasswordConfirmed(): void
    {
        if ($this->passwordConfirmed()) {
            return;
        }

        // Livewire runs no redirect of a component that mounts inside an update, so throw the redirect.
        abort(to_route('password.confirm'));
    }
}
