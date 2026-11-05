<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\CanBeDisabled;
use Livewire\Attributes\On;

abstract class Modal extends Component
{
    use CanBeDisabled;

    public bool $showing = false;

    #[On('show')]
    public function show(): void
    {
        $this->resetValidation();

        $this->showing = true;
    }

    public function close(): void
    {
        $this->showing = false;
    }

    public function updatedShowing(): void
    {
        $this->close();
    }
}
