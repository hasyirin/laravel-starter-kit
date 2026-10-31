<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Local\Examples;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;

class Modal extends \App\Livewire\Modal
{
    #[Validate('required')]
    public string $name = '';

    public function save(): void
    {
        $this->ensureEnabled();

        $this->validate();

        $this->reset('name');

        Flux::toast(variant: 'success', text: __('docs.examples.modal.saved'));

        $this->close();
    }

    public function render(): View
    {
        return view('livewire.pages.local.examples.modal');
    }
}
