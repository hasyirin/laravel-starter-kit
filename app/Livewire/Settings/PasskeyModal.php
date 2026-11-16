<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Livewire\Concerns\ConfirmsPassword;
use App\Livewire\Concerns\RequiresVerification;
use App\Livewire\Modal;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;

class PasskeyModal extends Modal
{
    use ConfirmsPassword;
    use RequiresVerification;

    public static function event(): string
    {
        return static::class.':saved';
    }

    #[On('show')]
    public function show(): void
    {
        if (! $this->passwordConfirmed()) {
            return;
        }

        parent::show();
    }

    public function registered(): void
    {
        $this->dispatch(self::event());

        $this->close();
    }

    public function render(): View
    {
        return view('livewire.settings.passkey-modal');
    }
}
