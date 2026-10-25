<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;

class AbilityCheckFormHost extends Component
{
    public AbilityCheckForm $form;

    public function mount(): void
    {
        $this->form->check();
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
