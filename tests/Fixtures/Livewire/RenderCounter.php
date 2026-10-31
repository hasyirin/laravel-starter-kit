<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;

class RenderCounter extends Component
{
    public int $renders = 0;

    public function render(): string
    {
        $this->renders++;

        return <<<'BLADE'
            <div>Renders: {{ $renders }}</div>
            BLADE;
    }
}
