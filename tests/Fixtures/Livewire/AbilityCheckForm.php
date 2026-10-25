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
            'can' => $this->can('admin.access'),
            'cannot' => $this->cannot('admin.access'),
            'canAny' => $this->canAny(['admin.access', 'admin.horizon.view']),
            'cannotAny' => $this->cannotAny(['admin.access', 'admin.horizon.view']),
        ];
    }
}
