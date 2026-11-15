<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;

class InnerValidationFormHost extends Component
{
    public InnerValidationForm $innerForm;

    public function save(): void
    {
        $this->innerForm->save();
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
