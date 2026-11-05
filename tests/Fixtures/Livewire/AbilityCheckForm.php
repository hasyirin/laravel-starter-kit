<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Form;

class AbilityCheckForm extends Form
{
    /**
     * @var array<string, bool>
     */
    public array $results = [];

    public function check(): void
    {
        $this->results = [
            'can' => $this->can('system.access'),
            'cannot' => $this->cannot('system.access'),
            'canAny' => $this->canAny(['system.access', 'system.horizon.view']),
            'cannotAny' => $this->cannotAny(['system.access', 'system.horizon.view']),
        ];
    }
}
