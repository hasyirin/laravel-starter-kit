<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;

class VerifiedPanelHost extends Component
{
    public bool $showingPanel = false;

    public function show(): void
    {
        $this->showingPanel = true;
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                @if ($showingPanel)
                    @livewire(\Tests\Fixtures\Livewire\VerifiedPanel::class)
                @endif
            </div>
            BLADE;
    }
}
