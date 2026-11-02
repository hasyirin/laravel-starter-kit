<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\CanBeDisabled;
use Livewire\Attributes\On;

abstract class Modal extends Component
{
    use CanBeDisabled;

    public bool $open = false;

    #[On('show')]
    public function show(): void
    {
        $this->resetValidation();

        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function updatedOpen(): void
    {
        $this->close();
    }
}
