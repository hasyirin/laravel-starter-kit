<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;
use Tests\Fixtures\Enums\Priority;
use Tests\Fixtures\Enums\Status;

class EnumProperties extends Component
{
    public ?Status $status = Status::Draft;

    public ?Priority $priority = null;

    public function render(): string
    {
        return <<<'BLADE'
            <div>{{ $status?->value }} {{ $priority?->value }}</div>
            BLADE;
    }
}
