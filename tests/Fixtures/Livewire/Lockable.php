<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;
use App\Livewire\Concerns\CanBeDisabled;

class Lockable extends Component
{
    use CanBeDisabled;

    public bool $saved = false;

    public function save(): void
    {
        $this->ensureEnabled();

        $this->saved = true;
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>@if($disabled) Read only @else Editable @endif</div>
            BLADE;
    }
}
