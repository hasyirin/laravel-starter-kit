<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;

class AbilityCheck extends Component
{
    /**
     * @var array<string, bool>
     */
    public array $results = [];

    public function mount(): void
    {
        $this->results = [
            'can' => $this->can('system.access'),
            'cannot' => $this->cannot('system.access'),
            'canAny' => $this->canAny(['system.access', 'system.horizon.view']),
            'cannotAny' => $this->cannotAny(['system.access', 'system.horizon.view']),
        ];
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
