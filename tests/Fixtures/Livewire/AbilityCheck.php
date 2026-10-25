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
            'can' => $this->can('admin.access'),
            'cannot' => $this->cannot('admin.access'),
            'canAny' => $this->canAny(['admin.access', 'admin.horizon.view']),
            'cannotAny' => $this->cannotAny(['admin.access', 'admin.horizon.view']),
        ];
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
